<?php

namespace AIAgent\Tests\Unit\Support;

/**
 * Factory for creating test posts
 */
class PostFactory
{
    private static int $postCounter = 1;

    /**
     * Create a test post
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function create(array $overrides = []): array
    {
        $postId = self::$postCounter++;
        
        $defaults = [
            'ID' => $postId,
            'post_title' => "Test Post {$postId}",
            'post_content' => "This is test content for post {$postId}",
            'post_excerpt' => "Test excerpt for post {$postId}",
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
            'post_name' => "test-post-{$postId}",
            'post_parent' => 0,
            'menu_order' => 0,
            'comment_count' => 0,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create a draft post
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function createDraft(array $overrides = []): array
    {
        return self::create(array_merge(['post_status' => 'draft'], $overrides));
    }

    /**
     * Create a private post
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function createPrivate(array $overrides = []): array
    {
        return self::create(array_merge(['post_status' => 'private'], $overrides));
    }

    /**
     * Create a page
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function createPage(array $overrides = []): array
    {
        return self::create(array_merge(['post_type' => 'page'], $overrides));
    }
}
