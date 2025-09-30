<?php

namespace AIAgent\Infrastructure\WC;

final class Mappers
{
	/**
	 * @param \WC_Product $product
	 * @return array<string,mixed>
	 */
	public static function mapProduct($product): array
	{
		return [
			'id' => $product->get_id(),
			'name' => $product->get_name(),
			'sku' => $product->get_sku(),
			'price' => (float) $product->get_price(),
			'currency' => get_woocommerce_currency(),
			'stock_status' => $product->get_stock_status(),
			'categories' => array_values(wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])),
		];
	}

	/**
	 * @param \WC_Order $order
	 * @return array<string,mixed>
	 */
	public static function mapOrder($order): array
	{
		$items = [];
		foreach ($order->get_items() as $item) {
			$items[] = [
				'product_id' => $item->get_product_id(),
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'total' => (float) $item->get_total(),
			];
		}
		return [
			'id' => $order->get_id(),
			'number' => $order->get_order_number(),
			'customer_id' => $order->get_user_id(),
			'total' => (float) $order->get_total(),
			'currency' => $order->get_currency(),
			'created_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
			'payment_method' => $order->get_payment_method(),
			'status' => $order->get_status(),
			'items' => $items,
		];
	}

	/**
	 * @param \WC_Customer $customer
	 * @return array<string,mixed>
	 */
	public static function mapCustomer($customer): array
	{
		return [
			'id' => $customer->get_id(),
			'email' => $customer->get_email(),
			'first_name' => $customer->get_first_name(),
			'last_name' => $customer->get_last_name(),
			'orders_count' => (int) $customer->get_order_count(),
			'total_spent' => (float) $customer->get_total_spent(),
		];
	}
}


