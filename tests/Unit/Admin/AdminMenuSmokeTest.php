<?php

namespace AIAgent\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use AIAgent\Admin\AdminMenu;

final class AdminMenuSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!isset($GLOBALS['wpdb'])) {
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public int $insert_id = 1;
                public string $last_error = '';
                public function get_var($q){ return 0; }
                public function get_results($q,$type=ARRAY_A){ return []; }
                public function prepare($q,$v=[]){ return $q; }
                public function insert($table, $data) { $this->insert_id = 1; return 1; }
                public function update($table, $data, $where = []) { return 1; }
                public function query($q){ return true; }
                public function get_charset_collate(){ return ''; }
                public function get_row($q, $type = ARRAY_A) { return null; }
            };
        }
    }

    public function testAddMenuPageDoesNotError(): void
    {
        $menu = new AdminMenu();
        $menu->addMenuPage();
        $this->assertTrue(true);
    }

    public function testRenderPagesDoNotError(): void
    {
        $menu = new AdminMenu();
        // Dashboard
        ob_start(); $menu->renderDashboardPage(); ob_end_clean();
        // Logs
        ob_start(); $menu->renderLogsPage(); ob_end_clean();
        // Tools
        ob_start(); $menu->renderToolsPage(); ob_end_clean();
        // Policies
        ob_start(); $menu->renderPoliciesPage(); ob_end_clean();
        $this->assertTrue(true);
    }
}


