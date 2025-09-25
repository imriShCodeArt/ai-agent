<?php

namespace AIAgent\Install;

use AIAgent\Infrastructure\Database\MigrationManager;
use AIAgent\Infrastructure\Security\RoleManager;
use AIAgent\Support\Logger;

final class Activator
{
    private MigrationManager $migrationManager;
    private RoleManager $roleManager;
    private Logger $logger;

    public function __construct(
        MigrationManager $migrationManager,
        RoleManager $roleManager,
        Logger $logger
    ) {
        $this->migrationManager = $migrationManager;
        $this->roleManager = $roleManager;
        $this->logger = $logger;
    }

    public function activate(): void
    {
        $this->logger->info('Starting AI Agent plugin activation');

        try {
            // Create database tables
            $this->migrationManager->runMigrations();
            $this->logger->info('Database migrations completed');

            // Create AI Agent role and capabilities
            $this->roleManager->createRole();
            $this->logger->info('AI Agent role created');

            // Create service user
            $serviceUserId = $this->roleManager->createServiceUser();
            $this->logger->info('AI Agent service user created', ['user_id' => $serviceUserId]);

            // Set default options
            $this->setDefaultOptions();

            $this->logger->info('AI Agent plugin activation completed successfully');

        } catch (\Exception $e) {
            $this->logger->error('AI Agent plugin activation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function deactivate(): void
    {
        $this->logger->info('Starting AI Agent plugin deactivation');

        try {
            // Note: We don't remove the role or service user on deactivation
            // to preserve data integrity. They will be cleaned up on uninstall.

            $this->logger->info('AI Agent plugin deactivation completed');

        } catch (\Exception $e) {
            $this->logger->error('AI Agent plugin deactivation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function uninstall(): void
    {
        $this->logger->info('Starting AI Agent plugin uninstall');

        try {
            // Remove role and capabilities
            $this->roleManager->removeRole();
            $this->logger->info('AI Agent role removed');

            // Rollback all migrations (drop tables)
            $this->migrationManager->rollbackMigrations(999); // Rollback all
            $this->logger->info('Database tables removed');

            // Remove options
            $this->removeOptions();

            $this->logger->info('AI Agent plugin uninstall completed');

        } catch (\Exception $e) {
            $this->logger->error('AI Agent plugin uninstall failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function setDefaultOptions(): void
    {
        $defaultOptions = [
            'ai_agent_version' => '0.1.1',
            'ai_agent_mode' => 'suggest', // suggest, review, autonomous
            'ai_agent_allowed_post_types' => ['post', 'page'],
            'ai_agent_rate_limit_hourly' => 20,
            'ai_agent_rate_limit_daily' => 100,
            'ai_agent_require_approval' => true,
            'ai_agent_auto_publish' => false,
            'ai_agent_blocked_terms' => ['spam', 'scam'],
            'ai_agent_allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
            'ai_agent_woocommerce_enabled' => false,
        ];

        foreach ($defaultOptions as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }

        $this->logger->info('Default options set', ['count' => count($defaultOptions)]);
    }

    private function removeOptions(): void
    {
        $options = [
            'ai_agent_version',
            'ai_agent_mode',
            'ai_agent_allowed_post_types',
            'ai_agent_rate_limit_hourly',
            'ai_agent_rate_limit_daily',
            'ai_agent_require_approval',
            'ai_agent_auto_publish',
            'ai_agent_blocked_terms',
            'ai_agent_allowed_hours',
            'ai_agent_woocommerce_enabled',
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        $this->logger->info('Plugin options removed', ['count' => count($options)]);
    }
}
