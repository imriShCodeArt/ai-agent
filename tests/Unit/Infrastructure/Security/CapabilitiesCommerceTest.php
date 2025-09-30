<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Security\Capabilities;

final class CapabilitiesCommerceTest extends TestCase
{
    public function testManageProductsCapabilityExists(): void
    {
        $all = Capabilities::getAll();
        $this->assertContains(Capabilities::MANAGE_PRODUCTS, $all);
        $this->assertSame('Manage WooCommerce products via AI Agent', Capabilities::getDescription(Capabilities::MANAGE_PRODUCTS));
    }
}


