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
				'title' => ['type' => 'string', 'minLength' => 1],
				'description' => ['type' => 'string'],
				'price' => ['type' => 'number'],
				'stock' => ['type' => 'integer'],
				'sku' => ['type' => 'string'],
				'categories' => ['type' => 'array'],
				'images' => ['type' => 'array'],
			],
		];
	}
	public function execute(array $input): array
	{
		if (!get_option('ai_agent_woocommerce_enabled', false)) {
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
		$productId = $product->save();
		return ['ok' => true, 'id' => $productId];
	}
}
