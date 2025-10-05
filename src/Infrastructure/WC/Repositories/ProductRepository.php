<?php

namespace AIAgent\Infrastructure\WC\Repositories;

final class ProductRepository
{
    /**
     * @param array<string,mixed> $args
     * @return array<int,object>
     */
    public function search(array $args = []): array
    {
        if (!function_exists('wc_get_products')) {
            return [];
        }
        return (array) \wc_get_products($args);
    }

    /**
     * @return object|null
     */
    public function getById(int $id)
    {
        if (!function_exists('wc_get_product')) {
            return null;
        }
        return \wc_get_product($id) ?: null;
    }
}


