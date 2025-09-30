<?php

namespace AIAgent\Infrastructure\Tools;

final class WCProductsSearchTool implements ToolInterface
{
	public function getName(): string { return 'products.search'; }
	public function getDescription(): string { return 'Search WooCommerce products by filters (sku, category, price).'; }
    public function getInputSchema(): array
	{
		return [
			'required' => [],
			'properties' => [
				'q' => ['type' => 'string'],
				'sku' => ['type' => 'string'],
				'category' => ['type' => 'string'],
				'price_min' => ['type' => 'number', 'minimum' => 0],
				'price_max' => ['type' => 'number', 'minimum' => 0],
				'page' => ['type' => 'integer'],
				'per_page' => ['type' => 'integer'],
			],
		];
	}
	public function execute(array $input): array
	{
        if (!Validator::validate($this->getInputSchema(), $input)) {
			return ['ok' => false, 'error' => 'validation_failed'];
		}
		$enabled = (bool) apply_filters('ai_agent_wc_enabled', (bool) get_option('ai_agent_woocommerce_enabled', false));
		if (!$enabled) {
			return ['ok' => false, 'error' => 'WooCommerce disabled'];
		}
		if (!function_exists('wc_get_products')) {
			return ['ok' => false, 'error' => 'WooCommerce not available'];
		}
		$args = [
			'limit' => min((int) ($input['per_page'] ?? 20), 50),
			'page' => max(1, (int) ($input['page'] ?? 1)),
			'status' => 'publish',
		];
		if (!empty($input['sku'])) { $args['sku'] = sanitize_text_field((string) $input['sku']); }
		if (!empty($input['category'])) { $args['category'] = sanitize_title((string) $input['category']); }
		if (!empty($input['q'])) { $args['search'] = sanitize_text_field((string) $input['q']); }
		$products = wc_get_products($args);
		$items = [];
		foreach ($products as $product) {
			$price = (float) $product->get_price();
			$min = isset($input['price_min']) ? (float) $input['price_min'] : null;
			$max = isset($input['price_max']) ? (float) $input['price_max'] : null;
			if ($min !== null && $price < $min) { continue; }
			if ($max !== null && $price > $max) { continue; }
			$items[] = [
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'sku' => $product->get_sku(),
				'price' => $product->get_price(),
				'stock_status' => $product->get_stock_status(),
			];
		}
		return ['ok' => true, 'items' => $items, 'page' => $args['page'], 'per_page' => $args['limit']];
	}
}
