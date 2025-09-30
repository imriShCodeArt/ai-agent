<?php

namespace AIAgent\Infrastructure\WC\Repositories;

final class OrderRepository
{
    /**
     * @param array<string,mixed> $args
     * @return array<int,object>
     */
    public function search(array $args = []): array
    {
        if (!class_exists('WC_Order_Query')) { return []; }
        $query = new \WC_Order_Query($args);
        return (array) $query->get_orders();
    }
}


