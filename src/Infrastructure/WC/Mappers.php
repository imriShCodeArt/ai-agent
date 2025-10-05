<?php

namespace AIAgent\Infrastructure\WC;

final class Mappers
{
    /**
     * @param object $product
     * @return array<string,mixed>
     */
    public static function mapProduct($product): array
	{
		return [
			// @phpstan-ignore-next-line dynamic WooCommerce runtime types
			'id' => $product->get_id(),
			// @phpstan-ignore-next-line
			'name' => $product->get_name(),
			// @phpstan-ignore-next-line
			'sku' => $product->get_sku(),
			// @phpstan-ignore-next-line
			'price' => (float) $product->get_price(),
			'currency' => \get_woocommerce_currency(),
			// @phpstan-ignore-next-line
			'stock_status' => $product->get_stock_status(),
			// @phpstan-ignore-next-line
			'categories' => array_values(\wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])),
		];
	}

    /**
     * @param object $order
     * @return array<string,mixed>
     */
    public static function mapOrder($order): array
	{
		$items = [];
		// @phpstan-ignore-next-line dynamic WooCommerce runtime types
		foreach ($order->get_items() as $item) {
			$items[] = [
				// @phpstan-ignore-next-line
				'product_id' => $item->get_product_id(),
				// @phpstan-ignore-next-line
				'name' => $item->get_name(),
				// @phpstan-ignore-next-line
				'quantity' => $item->get_quantity(),
				// @phpstan-ignore-next-line
				'total' => (float) $item->get_total(),
			];
		}
		return [
			// @phpstan-ignore-next-line
			'id' => $order->get_id(),
			// @phpstan-ignore-next-line
			'number' => $order->get_order_number(),
			// @phpstan-ignore-next-line
			'customer_id' => $order->get_user_id(),
			// @phpstan-ignore-next-line
			'total' => (float) $order->get_total(),
			// @phpstan-ignore-next-line
			'currency' => $order->get_currency(),
			// @phpstan-ignore-next-line
			'created_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
			// @phpstan-ignore-next-line
			'payment_method' => $order->get_payment_method(),
			// @phpstan-ignore-next-line
			'status' => $order->get_status(),
			'items' => $items,
		];
	}

    /**
     * @param object $customer
     * @return array<string,mixed>
     */
    public static function mapCustomer($customer): array
	{
		return [
			// @phpstan-ignore-next-line
			'id' => $customer->get_id(),
			// @phpstan-ignore-next-line
			'email' => $customer->get_email(),
			// @phpstan-ignore-next-line
			'first_name' => $customer->get_first_name(),
			// @phpstan-ignore-next-line
			'last_name' => $customer->get_last_name(),
			// @phpstan-ignore-next-line
			'orders_count' => (int) $customer->get_order_count(),
			// @phpstan-ignore-next-line
			'total_spent' => (float) $customer->get_total_spent(),
		];
	}
}


