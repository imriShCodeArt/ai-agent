<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Support\Logger;

final class WooCommerceController extends BaseRestController
{
	private Logger $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * GET /ai-agent/v1/wc/products
	 * Read-only product search (title, sku, category, price range)
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function productsSearch($request)
	{
		if (!function_exists('get_post_type_object')) {
			return new \WP_Error('wp_missing', 'WordPress functions unavailable');
		}

		$enabled = (bool) get_option('ai_agent_woocommerce_enabled', false);
		if (!$enabled) {
			return new \WP_Error('wc_disabled', 'WooCommerce integration is disabled');
		}

		// Guard WooCommerce dependency
		if (!class_exists('WC_Product')) {
			return new \WP_Error('wc_missing', 'WooCommerce is not installed/active');
		}

		$args = [
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => min((int) ($request->get_param('per_page') ?? 20), 50),
			'paged' => max(1, (int) ($request->get_param('page') ?? 1)),
			's' => sanitize_text_field((string) ($request->get_param('q') ?? '')),
		];

		$metaQuery = [];
		$sku = (string) ($request->get_param('sku') ?? '');
		if ($sku !== '') {
			$metaQuery[] = [
				'key' => '_sku',
				'value' => sanitize_text_field($sku),
				'compare' => '=',
			];
		}

		$priceMin = $request->get_param('price_min');
		$priceMax = $request->get_param('price_max');
		if ($priceMin !== null || $priceMax !== null) {
			$range = ['key' => '_price', 'type' => 'NUMERIC'];
			if ($priceMin !== null) {
				$range['value'][] = (float) $priceMin;
			}
			if ($priceMax !== null) {
				$range['value'][] = (float) $priceMax;
			}
			$range['compare'] = (isset($range['value'][0]) && isset($range['value'][1])) ? 'BETWEEN' : '>=';
			$metaQuery[] = $range;
		}

		if (!empty($metaQuery)) {
			$args['meta_query'] = $metaQuery;
		}

		$taxQuery = [];
		$category = (string) ($request->get_param('category') ?? '');
		if ($category !== '') {
			$taxQuery[] = [
				'taxonomy' => 'product_cat',
				'field' => 'slug',
				'terms' => sanitize_title($category),
			];
		}
		if (!empty($taxQuery)) {
			$args['tax_query'] = $taxQuery;
		}

		$query = new \WP_Query($args);
		$items = [];
		if ($query->have_posts()) {
			foreach ($query->posts as $post) {
				$product = wc_get_product($post->ID);
				if (!$product) {
					continue;
				}
				$items[] = [
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'sku' => $product->get_sku(),
					'price' => $product->get_price(),
					'stock_status' => $product->get_stock_status(),
				];
			}
		}

		return new \WP_REST_Response([
			'items' => $items,
			'pagination' => [
				'page' => (int) $args['paged'],
				'per_page' => (int) $args['posts_per_page'],
				'total' => (int) $query->found_posts,
			],
		]);
	}
}
