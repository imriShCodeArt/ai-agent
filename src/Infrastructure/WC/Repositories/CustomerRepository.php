<?php

namespace AIAgent\Infrastructure\WC\Repositories;

final class CustomerRepository
{
    /**
     * @param array<string,mixed> $args
     * @return array<int,object>
     */
    public function search(array $args = []): array
    {
        $query = new \WP_User_Query($args);
        return (array) $query->get_results();
    }
}


