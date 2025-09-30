<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Support\Logger;
use AIAgent\Infrastructure\WC\Mappers;

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
    /**
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    /**
     * @param mixed $request
     */
    public function productsSearch($request)
	{
		if (!function_exists('get_post_type_object')) {
			return new \WP_Error('wp_missing', 'WordPress functions unavailable');
		}

		$enabled = (bool) get_option('ai_agent_woocommerce_enabled', false);
		$enabled = (bool) apply_filters('ai_agent_wc_enabled', $enabled);
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
            $values = [];
            if ($priceMin !== null) { $values[] = (float) $priceMin; }
            if ($priceMax !== null) { $values[] = (float) $priceMax; }
            $metaQuery[] = [
                'key' => '_price',
                'type' => 'NUMERIC',
                'value' => $values,
                'compare' => count($values) === 2 ? 'BETWEEN' : '>=',
            ];
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

		// Batch pagination safeguards
        $args['posts_per_page'] = min((int) $args['posts_per_page'], 50);
        $args['paged'] = max((int) $args['paged'], 1);
		// Simple object cache to avoid duplicate queries in short window
		$cacheKey = 'ai_agent_wc_products_' . md5(wp_json_encode($args));
		$cached = wp_cache_get($cacheKey, 'ai_agent');
		if ($cached !== false) {
			return new \WP_REST_Response($cached);
		}
		$query = new \WP_Query($args);
		$items = [];
		// @phpstan-ignore-next-line accessing dynamic WP_Query props
		if ($query->have_posts()) {
			// @phpstan-ignore-next-line WP_Query->posts exists at runtime
			foreach ($query->posts as $post) {
				$product = wc_get_product($post->ID);
				if (!$product) {
					continue;
				}
				$items[] = Mappers::mapProduct($product);
			}
		}

		$response = [
			'items' => $items,
			'pagination' => [
				'page' => (int) $args['paged'],
				'per_page' => (int) $args['posts_per_page'],
				// @phpstan-ignore-next-line WP_Query->found_posts exists at runtime
				'total' => (int) $query->found_posts,
			],
		];
		wp_cache_set($cacheKey, $response, 'ai_agent', 30);
		return new \WP_REST_Response($response);
	}

	/**
	 * GET /ai-agent/v1/wc/orders
	 */
    /**
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    /**
     * @param mixed $request
     */
    public function ordersSearch($request)
	{
		if (!class_exists('WC_Order_Query')) {
			return new \WP_Error('wc_missing', 'WooCommerce is not installed/active');
		}
		$args = [
			'limit' => min((int) ($request->get_param('per_page') ?? 20), 50),
			'page' => max(1, (int) ($request->get_param('page') ?? 1)),
			'orderby' => 'date',
			'order' => 'DESC',
		];
		if ($status = $request->get_param('status')) { $args['status'] = array_map('sanitize_text_field', (array) $status); }
		if ($customer = $request->get_param('customer_id')) { $args['customer'] = (int) $customer; }
		$query = new \WC_Order_Query($args);
		$orders = $query->get_orders();
		$items = [];
		foreach ($orders as $order) { $items[] = Mappers::mapOrder($order); }
        return new \WP_REST_Response(['items' => $items, 'page' => $args['page'], 'per_page' => $args['limit']]);
	}

	/**
	 * GET /ai-agent/v1/wc/customers
	 */
    /**
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    /**
     * @param mixed $request
     */
    public function customersSearch($request)
	{
		if (!class_exists('WC_Customer')) {
			return new \WP_Error('wc_missing', 'WooCommerce is not installed/active');
		}
		$role = 'customer';
		$args = [
			'role' => $role,
			'number' => min((int) ($request->get_param('per_page') ?? 20), 50),
			'paged' => max(1, (int) ($request->get_param('page') ?? 1)),
			'orderby' => 'ID',
			'order' => 'DESC',
			'search' => !empty($request->get_param('q')) ? '*' . esc_attr($request->get_param('q')) . '*' : '',
			'search_columns' => ['user_login', 'user_email', 'display_name'],
		];
		$userQuery = new \WP_User_Query($args);
		$items = [];
		foreach ($userQuery->get_results() as $user) { $items[] = Mappers::mapCustomer(new \WC_Customer($user->ID)); }
		return new \WP_REST_Response(['items' => $items, 'page' => $args['paged'], 'per_page' => $args['number']]);
	}
}
