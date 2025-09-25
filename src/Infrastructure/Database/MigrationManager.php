<?php

namespace AIAgent\Infrastructure\Database;

use AIAgent\Support\Logger;

final class MigrationManager
{
    private Logger $logger;
    private string $tableName;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->tableName = 'ai_agent_migrations';
    }

    public function runMigrations(): void
    {
        $this->ensureMigrationsTable();
        $migrations = $this->getMigrations();
        $appliedMigrations = $this->getAppliedMigrations();

        foreach ($migrations as $migration) {
            if (!in_array($migration->getVersion(), $appliedMigrations, true)) {
                $this->runMigration($migration);
            }
        }
    }

    public function rollbackMigrations(int $count = 1): void
    {
        $this->ensureMigrationsTable();
        $appliedMigrations = $this->getAppliedMigrations();
        $migrations = $this->getMigrations();
        $migrationsToRollback = array_slice(array_reverse($appliedMigrations), 0, $count);

        foreach ($migrationsToRollback as $version) {
            $migration = $this->findMigrationByVersion($migrations, $version);
            if ($migration) {
                $this->rollbackMigration($migration);
            }
        }
    }

    private function ensureMigrationsTable(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . $this->tableName;

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            version varchar(255) NOT NULL,
            description text NOT NULL,
            applied_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private function getMigrations(): array
    {
        return [
            new Migration(
                '001_create_actions_table',
                'Create actions table for audit logging',
                [$this, 'createActionsTable'],
                [$this, 'dropActionsTable']
            ),
            new Migration(
                '002_create_sessions_table',
                'Create sessions table for chat transcripts',
                [$this, 'createSessionsTable'],
                [$this, 'dropSessionsTable']
            ),
            new Migration(
                '003_create_policies_table',
                'Create policies table for versioned policies',
                [$this, 'createPoliciesTable'],
                [$this, 'dropPoliciesTable']
            ),
        ];
    }

    private function getAppliedMigrations(): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tableName;

        $results = $wpdb->get_col("SELECT version FROM $table_name ORDER BY applied_at");
        return $results ?: [];
    }

    private function findMigrationByVersion(array $migrations, string $version): ?Migration
    {
        foreach ($migrations as $migration) {
            if ($migration->getVersion() === $version) {
                return $migration;
            }
        }
        return null;
    }

    private function runMigration(Migration $migration): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tableName;

        try {
            $migration->up();
            $wpdb->insert(
                $table_name,
                [
                    'version' => $migration->getVersion(),
                    'description' => $migration->getDescription(),
                ]
            );
            $this->logger->info('Migration applied', ['version' => $migration->getVersion()]);
        } catch (\Exception $e) {
            $this->logger->error('Migration failed', [
                'version' => $migration->getVersion(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function rollbackMigration(Migration $migration): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tableName;

        try {
            $migration->down();
            $wpdb->delete($table_name, ['version' => $migration->getVersion()]);
            $this->logger->info('Migration rolled back', ['version' => $migration->getVersion()]);
        } catch (\Exception $e) {
            $this->logger->error('Migration rollback failed', [
                'version' => $migration->getVersion(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createActionsTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ts datetime DEFAULT CURRENT_TIMESTAMP,
            user_id bigint(20) NOT NULL,
            tool varchar(255) NOT NULL,
            entity_type varchar(100) NOT NULL,
            entity_id bigint(20) DEFAULT NULL,
            mode varchar(50) NOT NULL,
            payload_redacted longtext,
            before_hash varchar(64) DEFAULT NULL,
            after_hash varchar(64) DEFAULT NULL,
            diff_html mediumtext,
            status varchar(50) NOT NULL,
            error text DEFAULT NULL,
            policy_verdict longtext,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY tool (tool),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY status (status),
            KEY ts (ts)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function dropActionsTable(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_actions';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public function createSessionsTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ai_agent_sessions';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            messages longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function dropSessionsTable(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_sessions';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public function createPoliciesTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ai_agent_policies';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            version varchar(50) NOT NULL,
            policy_data longtext NOT NULL,
            is_active tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY version (version),
            KEY is_active (is_active),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function dropPoliciesTable(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_policies';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
