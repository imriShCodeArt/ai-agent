<?php
namespace AIAgent\Infrastructure\Tools;

final class PostsCreateTool implements ToolInterface
{
    public function getName(): string
    {
        return 'posts.create';
    }

    public function getInputSchema(): array
    {
        return [
            'required' => ['fields'],
            'properties' => [
                'fields' => [
                    'type' => 'object',
                ],
            ],
        ];
    }

    public function execute(array $input): array
    {
        if (!Validator::validate($this->getInputSchema(), $input)) {
            return ['error' => 'invalid_input'];
        }
        if (!function_exists('wp_insert_post')) {
            return ['error' => 'wp_unavailable'];
        }

        $fields = $input['fields'];
        $postArr = [
            'post_title' => sanitize_text_field($fields['post_title'] ?? ''),
            'post_content' => wp_kses_post($fields['post_content'] ?? ''),
            'post_status' => sanitize_text_field($fields['post_status'] ?? 'draft'),
            'post_type' => sanitize_text_field($fields['post_type'] ?? 'post'),
        ];
        $postId = wp_insert_post($postArr, true);
        if (is_wp_error($postId)) {
            return ['error' => 'insert_failed', 'message' => $postId->get_error_message()];
        }
        return ['id' => (int) $postId];
    }
}


