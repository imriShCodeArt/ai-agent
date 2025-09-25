<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class PostsController extends BaseRestController
{
    private Policy $policy;
    private AuditLogger $auditLogger;
    private Logger $logger;

    public function __construct(
        Policy $policy,
        AuditLogger $auditLogger,
        Logger $logger
    ) {
        $this->policy = $policy;
        $this->auditLogger = $auditLogger;
        $this->logger = $logger;
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $fields = $request->get_param('fields') ?? [];
        $mode = $request->get_param('mode') ?? 'suggest';

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        // Check capabilities
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions',
                ['status' => 403]
            );
        }

        // Check policy
        if (!$this->policy->isAllowed('posts.create', null, $fields)) {
            return new WP_Error(
                'policy_blocked',
                'Operation not allowed by policy',
                ['status' => 403]
            );
        }

        try {
            // Set default values
            $fields['post_status'] = $fields['post_status'] ?? 'draft';
            $fields['post_type'] = $fields['post_type'] ?? 'post';
            $fields['post_author'] = $userId;

            // Create the post
            $postId = wp_insert_post($fields, true);

            if (is_wp_error($postId)) {
                throw new \Exception($postId->get_error_message());
            }

            // Log the action
            $this->auditLogger->logAction(
                'posts.create',
                $userId,
                'post',
                $postId,
                $mode,
                $fields,
                'success'
            );

            $this->logger->info('Post created via AI Agent', [
                'post_id' => $postId,
                'user_id' => $userId,
                'mode' => $mode,
            ]);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id' => $postId,
                    'post' => get_post($postId),
                    'message' => 'Post created successfully',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Post creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'fields' => $fields,
            ]);

            $this->auditLogger->logAction(
                'posts.create',
                $userId,
                'post',
                null,
                $mode,
                $fields,
                'error',
                $e->getMessage()
            );

            return new WP_Error(
                'creation_failed',
                'Failed to create post: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $fields = $request->get_param('fields') ?? [];
        $mode = $request->get_param('mode') ?? 'suggest';

        if (!$id) {
            return new WP_Error(
                'missing_id',
                'Post ID is required',
                ['status' => 400]
            );
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        // Check if post exists
        $post = get_post($id);
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                'Post not found',
                ['status' => 404]
            );
        }

        // Check capabilities
        if (!current_user_can('edit_post', $id)) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions',
                ['status' => 403]
            );
        }

        // Check policy
        if (!$this->policy->isAllowed('posts.update', $id, $fields)) {
            return new WP_Error(
                'policy_blocked',
                'Operation not allowed by policy',
                ['status' => 403]
            );
        }

        try {
            // Create revision before updating
            wp_save_post_revision($id);

            // Add ID to fields
            $fields['ID'] = $id;

            // Update the post
            $result = wp_update_post($fields, true);

            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }

            // Log the action
            $this->auditLogger->logAction(
                'posts.update',
                $userId,
                'post',
                $id,
                $mode,
                $fields,
                'success'
            );

            $this->logger->info('Post updated via AI Agent', [
                'post_id' => $id,
                'user_id' => $userId,
                'mode' => $mode,
            ]);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'post' => get_post($id),
                    'message' => 'Post updated successfully',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Post update failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'post_id' => $id,
                'fields' => $fields,
            ]);

            $this->auditLogger->logAction(
                'posts.update',
                $userId,
                'post',
                $id,
                $mode,
                $fields,
                'error',
                $e->getMessage()
            );

            return new WP_Error(
                'update_failed',
                'Failed to update post: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function delete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $mode = $request->get_param('mode') ?? 'suggest';

        if (!$id) {
            return new WP_Error(
                'missing_id',
                'Post ID is required',
                ['status' => 400]
            );
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        // Check if post exists
        $post = get_post($id);
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                'Post not found',
                ['status' => 404]
            );
        }

        // Check capabilities
        if (!current_user_can('delete_post', $id)) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions',
                ['status' => 403]
            );
        }

        // Check policy
        if (!$this->policy->isAllowed('posts.delete', $id, [])) {
            return new WP_Error(
                'policy_blocked',
                'Operation not allowed by policy',
                ['status' => 403]
            );
        }

        try {
            // Trash the post (soft delete)
            $result = wp_trash_post($id);

            if (!$result) {
                throw new \Exception('Failed to trash post');
            }

            // Log the action
            $this->auditLogger->logAction(
                'posts.delete',
                $userId,
                'post',
                $id,
                $mode,
                [],
                'success'
            );

            $this->logger->info('Post deleted via AI Agent', [
                'post_id' => $id,
                'user_id' => $userId,
                'mode' => $mode,
            ]);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'message' => 'Post deleted successfully',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Post deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'post_id' => $id,
            ]);

            $this->auditLogger->logAction(
                'posts.delete',
                $userId,
                'post',
                $id,
                $mode,
                [],
                'error',
                $e->getMessage()
            );

            return new WP_Error(
                'deletion_failed',
                'Failed to delete post: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        if (!$id) {
            return new WP_Error(
                'missing_id',
                'Post ID is required',
                ['status' => 400]
            );
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        // Check if post exists
        $post = get_post($id);
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                'Post not found',
                ['status' => 404]
            );
        }

        // Check capabilities
        if (!current_user_can('read_post', $id)) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions',
                ['status' => 403]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $post,
        ]);
    }
}
