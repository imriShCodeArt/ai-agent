<?php

namespace AIAgent\Infrastructure\Database\Migrations;

use AIAgent\Infrastructure\Database\Migration;

class EnhanceAuditLogTable extends Migration
{
    public function up(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_audit_log';
        
        // Check if table exists, if not create it
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                action varchar(100) NOT NULL,
                user_id bigint(20) NOT NULL,
                entity_type varchar(50) DEFAULT NULL,
                entity_id bigint(20) DEFAULT NULL,
                mode varchar(50) DEFAULT 'suggest',
                data longtext DEFAULT NULL,
                status varchar(50) NOT NULL,
                policy_version varchar(20) DEFAULT NULL,
                policy_verdict varchar(20) DEFAULT NULL,
                policy_reason varchar(100) DEFAULT NULL,
                policy_details text DEFAULT NULL,
                error_code varchar(50) DEFAULT NULL,
                error_category varchar(50) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY action (action),
                KEY user_id (user_id),
                KEY entity_type (entity_type),
                KEY entity_id (entity_id),
                KEY status (status),
                KEY policy_version (policy_version),
                KEY error_category (error_category),
                KEY created_at (created_at),
                KEY ip_address (ip_address)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $this->logger->info('Created enhanced ai_agent_audit_log table');
        } else {
            // Add new columns to existing table
            $columns_to_add = [
                'policy_version' => 'varchar(20) DEFAULT NULL',
                'policy_verdict' => 'varchar(20) DEFAULT NULL',
                'policy_reason' => 'varchar(100) DEFAULT NULL',
                'policy_details' => 'text DEFAULT NULL',
                'error_code' => 'varchar(50) DEFAULT NULL',
                'error_category' => 'varchar(50) DEFAULT NULL',
                'ip_address' => 'varchar(45) DEFAULT NULL',
                'user_agent' => 'text DEFAULT NULL',
            ];
            
            foreach ($columns_to_add as $column => $definition) {
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column'");
                
                if (!$column_exists) {
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column $definition");
                    $this->logger->info("Added column $column to ai_agent_audit_log table");
                }
            }
            
            // Add new indexes
            $indexes_to_add = [
                'policy_version' => 'policy_version',
                'error_category' => 'error_category',
                'ip_address' => 'ip_address',
            ];
            
            foreach ($indexes_to_add as $index_name => $column) {
                $index_exists = $wpdb->get_var("SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'");
                
                if (!$index_exists) {
                    $wpdb->query("ALTER TABLE $table_name ADD INDEX $index_name ($column)");
                    $this->logger->info("Added index $index_name to ai_agent_audit_log table");
                }
            }
            
            $this->logger->info('Enhanced existing ai_agent_audit_log table');
        }
    }
    
    public function down(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_audit_log';
        
        // Remove added columns
        $columns_to_remove = [
            'policy_version',
            'policy_verdict', 
            'policy_reason',
            'policy_details',
            'error_code',
            'error_category',
            'ip_address',
            'user_agent',
        ];
        
        foreach ($columns_to_remove as $column) {
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column'");
            
            if ($column_exists) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column");
                $this->logger->info("Removed column $column from ai_agent_audit_log table");
            }
        }
        
        // Remove added indexes
        $indexes_to_remove = [
            'policy_version',
            'error_category',
            'ip_address',
        ];
        
        foreach ($indexes_to_remove as $index) {
            $index_exists = $wpdb->get_var("SHOW INDEX FROM $table_name WHERE Key_name = '$index'");
            
            if ($index_exists) {
                $wpdb->query("ALTER TABLE $table_name DROP INDEX $index");
                $this->logger->info("Removed index $index from ai_agent_audit_log table");
            }
        }
    }
}
