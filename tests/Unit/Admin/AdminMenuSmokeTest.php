<?php

namespace AIAgent\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use AIAgent\Admin\AdminMenu;

final class AdminMenuSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        // WP admin stubs
        if (!function_exists('add_menu_page')) {
            function add_menu_page($a,$b,$c,$d,$e=null,$f=null,$g=null) {}
        }
        if (!function_exists('add_submenu_page')) {
            function add_submenu_page($a,$b,$c,$d,$e,$f=null) {}
        }
        if (!function_exists('wp_redirect')) {
            function wp_redirect($url) { return true; }
        }
        if (!function_exists('admin_url')) {
            function admin_url($path='') { return '/wp-admin/'.$path; }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($cap) { return true; }
        }
        if (!function_exists('wp_die')) {
            function wp_die($msg) { throw new \RuntimeException($msg); }
        }
        if (!function_exists('get_role')) {
            function get_role($r) { return (object)['name'=>$r]; }
        }
        if (!function_exists('get_user_by')) {
            function get_user_by($f,$v) { return (object)['display_name'=>'User']; }
        }
        if (!function_exists('get_users')) {
            function get_users() { return [(object)['ID'=>1,'display_name'=>'User']]; }
        }
        if (!function_exists('selected')) {
            function selected($a,$b) { return $a===$b?'selected(1)':''; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($s) { return (string)$s; }
        }
        if (!function_exists('esc_attr')) {
            function esc_attr($s) { return (string)$s; }
        }
        if (!function_exists('wp_nonce_field')) {
            function wp_nonce_field($a,$b) { return ''; }
        }
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


