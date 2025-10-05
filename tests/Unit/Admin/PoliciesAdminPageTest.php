<?php

namespace AIAgent\Tests\Unit\Admin;

use AIAgent\Admin\AdminMenu;
use PHPUnit\Framework\TestCase;

final class PoliciesAdminPageTest extends TestCase
{
    public function testPoliciesPageRendersWithoutNotices(): void
    {
        $menu = new AdminMenu();
        ob_start();
        $menu->renderPoliciesPage();
        $out = (string) ob_get_clean();
        $this->assertStringContainsString('AI Agent Policies', $out);
        $this->assertStringContainsString('Visual Policy Editor', $out);
        $this->assertStringContainsString('Import / Export', $out);
        $this->assertStringContainsString('Version Comparison', $out);
    }
}


