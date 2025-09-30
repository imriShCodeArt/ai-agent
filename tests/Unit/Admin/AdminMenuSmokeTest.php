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
                public function get_var($q){ return 0; }
                public function get_results($q,$type=ARRAY_A){ return []; }
                public function prepare($q,$v=[]){ return $q; }
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


