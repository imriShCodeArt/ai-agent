<?php

namespace AIAgent\Infrastructure\Tools;

final class WCProductsCreateTool implements ToolInterface
{
	public function getName(): string { return 'products.create'; }
	public function getDescription(): string { return 'Create a WooCommerce product.'; }
	public function getSchema(): array
	{
		return [
			'required' => ['title', 'price'],
			'properties' => [
				'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
				'description' => ['type' => 'string'],
				'price' => ['type' => 'number', 'minimum' => 0],
				'stock' => ['type' => 'integer'],
				'sku' => ['type' => 'string'],
				'categories' => ['type' => 'array', 'items' => ['type' => 'string']],
				'images' => ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uri']],
			],
		];
	}
	public function execute(array $input): array
	{
		// Validate input
		if (!Validator::validate($this->getSchema(), $input)) {
			return ['ok' => false, 'error' => 'validation_failed'];
		}
		$enabled = (bool) apply_filters('ai_agent_wc_enabled', (bool) get_option('ai_agent_woocommerce_enabled', false));
		if (!$enabled) {
			return ['ok' => false, 'error' => 'WooCommerce disabled'];
		}
		if (!class_exists('WC_Product')) {
			return ['ok' => false, 'error' => 'WooCommerce not available'];
		}
		$title = sanitize_text_field((string) ($input['title'] ?? ''));
		if ($title === '') { return ['ok' => false, 'error' => 'Missing title']; }
		$price = (float) ($input['price'] ?? 0);
		/** @var \WC_Product_Simple $product */
		$product = new \WC_Product_Simple();
		$product->set_name($title);
		if (!empty($input['description'])) {
			$product->set_description(wp_kses_post((string) $input['description']));
		}
		$product->set_regular_price((string) $price);
		if (isset($input['stock'])) {
			$product->set_stock_quantity((int) $input['stock']);
		}
		if (!empty($input['sku'])) {
			$product->set_sku(sanitize_text_field((string) $input['sku']));
		}
		// Optional media sideloading for images
		$attachmentIds = [];
		if (!empty($input['images']) && is_array($input['images'])) {
			foreach ($input['images'] as $url) {
				$url = filter_var((string) $url, FILTER_VALIDATE_URL) ? (string) $url : '';
				if ($url === '') { continue; }
				$attachmentId = $this->sideloadImage($url, $title);
				if ($attachmentId) { $attachmentIds[] = $attachmentId; }
			}
		}
		if (!empty($attachmentIds)) {
			$product->set_gallery_image_ids($attachmentIds);
		}
		$productId = $product->save();
		return ['ok' => true, 'id' => $productId];
	}

	private function sideloadImage(string $url, string $title): int
	{
		if (!function_exists('media_sideload_image')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$attachmentId = 0;
		$tmp = download_url($url);
		if (is_wp_error($tmp)) {
			return 0;
		}
		$fileArray = [
			'name' => basename(parse_url($url, PHP_URL_PATH) ?: 'image.jpg'),
			'tmp_name' => $tmp,
		];
		$attachmentId = media_handle_sideload($fileArray, 0, $title);
		if (is_wp_error($attachmentId)) {
			@unlink($tmp);
			return 0;
		}
		return (int) $attachmentId;
	}
}
