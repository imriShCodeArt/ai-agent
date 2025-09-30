<?php

namespace AIAgent\Infrastructure\Tools;

final class WCProductsUpdateTool implements ToolInterface
{
	public function getName(): string { return 'products.update'; }
	public function getDescription(): string { return 'Update a WooCommerce product (partial).'; }
	public function getSchema(): array
	{
		return [
			'required' => ['id'],
			'properties' => [
				'id' => ['type' => 'integer'],
				'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
				'description' => ['type' => 'string'],
				'price' => ['type' => 'number', 'minimum' => 0],
				'stock' => ['type' => 'integer'],
				'sku' => ['type' => 'string'],
			],
		];
	}
	public function execute(array $input): array
	{
		if (!Validator::validate($this->getSchema(), $input)) {
			return ['ok' => false, 'error' => 'validation_failed'];
		}
		$enabled = (bool) apply_filters('ai_agent_wc_enabled', (bool) get_option('ai_agent_woocommerce_enabled', false));
		if (!$enabled) {
			return ['ok' => false, 'error' => 'WooCommerce disabled'];
		}
		if (!function_exists('wc_get_product')) {
			return ['ok' => false, 'error' => 'WooCommerce not available'];
		}
		$id = (int) ($input['id'] ?? 0);
		$product = wc_get_product($id);
		if (!$product) { return ['ok' => false, 'error' => 'Product not found']; }
		if (!empty($input['title'])) { $product->set_name(sanitize_text_field((string) $input['title'])); }
		if (!empty($input['description'])) { $product->set_description(wp_kses_post((string) $input['description'])); }
		if (isset($input['price'])) { $product->set_regular_price((string) ((float) $input['price'])); }
		if (isset($input['stock'])) { $product->set_stock_quantity((int) $input['stock']); }
		if (!empty($input['sku'])) { $product->set_sku(sanitize_text_field((string) $input['sku'])); }
		$productId = $product->save();
		return ['ok' => true, 'id' => $productId];
	}
}
