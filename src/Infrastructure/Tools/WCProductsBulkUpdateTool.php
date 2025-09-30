<?php

namespace AIAgent\Infrastructure\Tools;

final class WCProductsBulkUpdateTool implements ToolInterface
{
	public function getName(): string { return 'products.bulkUpdate'; }
	public function getDescription(): string { return 'Bulk update WooCommerce products.'; }
	public function getSchema(): array
	{
		return [
			'required' => ['items'],
			'properties' => [
				'items' => ['type' => 'array'],
			],
		];
	}
	public function execute(array $input): array
	{
		if (!get_option('ai_agent_woocommerce_enabled', false)) {
			return ['ok' => false, 'error' => 'WooCommerce disabled'];
		}
		if (!function_exists('wc_get_product')) {
			return ['ok' => false, 'error' => 'WooCommerce not available'];
		}
		$results = [];
		foreach (($input['items'] ?? []) as $row) {
			$id = (int) ($row['id'] ?? 0);
			$product = wc_get_product($id);
			if (!$product) { $results[] = ['id' => $id, 'ok' => false, 'error' => 'Not found']; continue; }
			if (isset($row['price'])) { $product->set_regular_price((string) ((float) $row['price'])); }
			if (isset($row['stock'])) { $product->set_stock_quantity((int) $row['stock']); }
			$productId = $product->save();
			$results[] = ['id' => $productId, 'ok' => true];
		}
		return ['ok' => true, 'results' => $results];
	}
}
