<?php

namespace AIAgent\Infrastructure\Database\Migrations;

use AIAgent\Infrastructure\Database\MigrationInterface;
use AIAgent\Support\Logger;

class CreatePoliciesTable implements MigrationInterface
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getVersion(): string
    {
        return '0003_create_policies_table';
    }

    public function getDescription(): string
    {
        return 'Create policies table for versioned policies';
    }

    public function up(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_policies';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            version varchar(20) NOT NULL,
            tool_name varchar(100) NOT NULL,
            policy_doc longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tool_name (tool_name),
            KEY version (version),
            KEY is_active (is_active),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $this->logger->info('Created ai_agent_policies table');
    }
    
    public function down(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_policies';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        $this->logger->info('Dropped ai_agent_policies table');
    }
}
