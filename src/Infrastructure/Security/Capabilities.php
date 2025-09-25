<?php

namespace AIAgent\Infrastructure\Security;

final class Capabilities
{
    public const READ = 'ai_agent_read';
    public const EDIT_POSTS = 'ai_agent_edit_posts';
    public const EDIT_OTHERS_POSTS = 'ai_agent_edit_others_posts';
    public const PUBLISH_POSTS = 'ai_agent_publish_posts';
    public const DELETE_POSTS = 'ai_agent_delete_posts';
    public const MANAGE_PRODUCTS = 'ai_agent_manage_products';
    public const MANAGE_MEDIA = 'ai_agent_manage_media';
    public const MANAGE_TERMS = 'ai_agent_manage_terms';
    public const MANAGE_POLICIES = 'ai_agent_manage_policies';
    public const VIEW_LOGS = 'ai_agent_view_logs';
    public const APPROVE_CHANGES = 'ai_agent_approve_changes';

    private const CAPABILITY_DESCRIPTIONS = [
        self::READ => 'Read AI Agent data',
        self::EDIT_POSTS => 'Edit posts via AI Agent',
        self::EDIT_OTHERS_POSTS => 'Edit others posts via AI Agent',
        self::PUBLISH_POSTS => 'Publish posts via AI Agent',
        self::DELETE_POSTS => 'Delete posts via AI Agent',
        self::MANAGE_PRODUCTS => 'Manage WooCommerce products via AI Agent',
        self::MANAGE_MEDIA => 'Manage media via AI Agent',
        self::MANAGE_TERMS => 'Manage taxonomies via AI Agent',
        self::MANAGE_POLICIES => 'Manage AI Agent policies',
        self::VIEW_LOGS => 'View AI Agent audit logs',
        self::APPROVE_CHANGES => 'Approve AI Agent changes',
    ];

    public static function getAll(): array
    {
        return array_keys(self::CAPABILITY_DESCRIPTIONS);
    }

    public static function getDescription(string $capability): string
    {
        return self::CAPABILITY_DESCRIPTIONS[$capability] ?? 'Unknown capability';
    }

    public static function getDescriptions(): array
    {
        return self::CAPABILITY_DESCRIPTIONS;
    }

    public static function getDefaultCapabilities(): array
    {
        return [
            self::READ,
            self::EDIT_POSTS,
            self::MANAGE_MEDIA,
            self::MANAGE_TERMS,
        ];
    }

    public static function getAdvancedCapabilities(): array
    {
        return [
            self::EDIT_OTHERS_POSTS,
            self::PUBLISH_POSTS,
            self::DELETE_POSTS,
            self::MANAGE_PRODUCTS,
            self::MANAGE_POLICIES,
            self::VIEW_LOGS,
            self::APPROVE_CHANGES,
        ];
    }
}
