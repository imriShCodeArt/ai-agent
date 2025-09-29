<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use AIAgent\Infrastructure\Security\Capabilities;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    public function testGetAllReturnsAllCapabilities(): void
    {
        $capabilities = Capabilities::getAll();
        
        $this->assertIsArray($capabilities);
        $this->assertContains(Capabilities::READ, $capabilities);
        $this->assertContains(Capabilities::EDIT_POSTS, $capabilities);
        $this->assertContains(Capabilities::MANAGE_POLICIES, $capabilities);
    }

    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $this->assertEquals('Read AI Agent data', Capabilities::getDescription(Capabilities::READ));
        $this->assertEquals('Edit posts via AI Agent', Capabilities::getDescription(Capabilities::EDIT_POSTS));
        $this->assertEquals('Manage AI Agent policies', Capabilities::getDescription(Capabilities::MANAGE_POLICIES));
    }

    public function testGetDescriptionReturnsUnknownForInvalidCapability(): void
    {
        $this->assertEquals('Unknown capability', Capabilities::getDescription('invalid_capability'));
    }

    public function testGetDescriptionsReturnsAllDescriptions(): void
    {
        $descriptions = Capabilities::getDescriptions();
        
        $this->assertIsArray($descriptions);
        $this->assertArrayHasKey(Capabilities::READ, $descriptions);
        $this->assertArrayHasKey(Capabilities::EDIT_POSTS, $descriptions);
        $this->assertEquals('Read AI Agent data', $descriptions[Capabilities::READ]);
    }

    public function testGetDefaultCapabilitiesReturnsBasicCapabilities(): void
    {
        $defaultCapabilities = Capabilities::getDefaultCapabilities();
        
        $this->assertIsArray($defaultCapabilities);
        $this->assertContains(Capabilities::READ, $defaultCapabilities);
        $this->assertContains(Capabilities::EDIT_POSTS, $defaultCapabilities);
    }

    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('ai_agent_read', Capabilities::READ);
        $this->assertEquals('ai_agent_edit_posts', Capabilities::EDIT_POSTS);
        $this->assertEquals('ai_agent_manage_policies', Capabilities::MANAGE_POLICIES);
    }
}
