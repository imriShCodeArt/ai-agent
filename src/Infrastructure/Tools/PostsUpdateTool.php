<?php
namespace AIAgent\Infrastructure\Tools;

final class PostsUpdateTool implements ToolInterface
{
    public function getName(): string
    {
        return 'posts.update';
    }

    public function getInputSchema(): array
    {
        return [
            'required' => ['id', 'fields'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'fields' => ['type' => 'object'],
            ],
        ];
    }

    public function execute(array $input): array
    {
        if (!Validator::validate($this->getInputSchema(), $input)) {
            return ['error' => 'invalid_input'];
        }
        if (!function_exists('wp_update_post')) {
            return ['error' => 'wp_unavailable'];
        }

        $id = (int) $input['id'];
        $fields = $input['fields'];
        $postArr = ['ID' => $id];
        if (isset($fields['post_title'])) { $postArr['post_title'] = sanitize_text_field($fields['post_title']); }
        if (isset($fields['post_content'])) { $postArr['post_content'] = wp_kses_post($fields['post_content']); }
        if (isset($fields['post_status'])) { $postArr['post_status'] = sanitize_text_field($fields['post_status']); }

        $result = wp_update_post($postArr, true);
        if (is_wp_error($result)) {
            return ['error' => 'update_failed', 'message' => $result->get_error_message()];
        }
        return ['id' => $id, 'updated' => true];
    }
}


