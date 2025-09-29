<?php

namespace AIAgent\Tests\Unit\Support;

/**
 * Factory for creating test users
 */
class UserFactory
{
    private static int $userCounter = 1;

    /**
     * Create a test user with specified capabilities
     *
     * @param array<string> $capabilities
     * @return array<string, mixed>
     */
    public static function create(array $capabilities = []): array
    {
        $userId = self::$userCounter++;
        
        return [
            'ID' => $userId,
            'user_login' => "test_user_{$userId}",
            'user_email' => "test{$userId}@example.com",
            'user_nicename' => "test-user-{$userId}",
            'display_name' => "Test User {$userId}",
            'user_registered' => current_time('mysql'),
            'capabilities' => array_merge(['read'], $capabilities),
        ];
    }

    /**
     * Create an admin user
     *
     * @return array<string, mixed>
     */
    public static function createAdmin(): array
    {
        return self::create([
            'manage_options',
            'edit_posts',
            'edit_users',
            'manage_categories',
        ]);
    }

    /**
     * Create an editor user
     *
     * @return array<string, mixed>
     */
    public static function createEditor(): array
    {
        return self::create([
            'edit_posts',
            'edit_others_posts',
            'publish_posts',
            'delete_posts',
        ]);
    }

    /**
     * Create a subscriber user
     *
     * @return array<string, mixed>
     */
    public static function createSubscriber(): array
    {
        return self::create(['read']);
    }
}
