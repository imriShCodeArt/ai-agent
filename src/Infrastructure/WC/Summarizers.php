<?php

namespace AIAgent\Infrastructure\WC;

final class Summarizers
{
    /**
     * @param object $order
     */
    public static function summarizeOrder($order): string
    {
        // @phpstan-ignore-next-line
        $status = method_exists($order, 'get_status') ? (string) $order->get_status() : 'unknown';
        // @phpstan-ignore-next-line
        $total = method_exists($order, 'get_total') ? (float) $order->get_total() : 0.0;
        // @phpstan-ignore-next-line
        $items = method_exists($order, 'get_items') ? (array) $order->get_items() : [];
        $lines = [];
        foreach ($items as $item) {
            // @phpstan-ignore-next-line
            $lines[] = (string) $item->get_name() . ' x' . (int) $item->get_quantity();
        }
        return 'Status: ' . $status . '; Total: ' . number_format($total, 2) . '; Items: ' . implode(', ', $lines);
    }

    /**
     * @param object $customer
     */
    public static function summarizeCustomer($customer): string
    {
        // @phpstan-ignore-next-line
        $name = trim((string) $customer->get_first_name() . ' ' . (string) $customer->get_last_name());
        // @phpstan-ignore-next-line
        $orders = (int) $customer->get_order_count();
        // @phpstan-ignore-next-line
        $spent = (float) $customer->get_total_spent();
        return 'Customer: ' . ($name !== '' ? $name : 'N/A') . '; Orders: ' . $orders . '; Spent: ' . number_format($spent, 2);
    }
}


