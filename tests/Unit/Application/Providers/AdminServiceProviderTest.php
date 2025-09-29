<?php

namespace AIAgent\Tests\Unit\Application\Providers;

use AIAgent\Application\Providers\AdminServiceProvider;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Hooks\HooksLoader;
use PHPUnit\Framework\TestCase;

class AdminServiceProviderTest extends TestCase
{
    private ServiceContainer $container;
    private HooksLoader $hooks;
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
        $this->hooks = new HooksLoader();
        $this->pluginFile = __DIR__ . '/../../../ai-agent.php';
    }

    public function testProviderCanBeInstantiated(): void
    {
        $provider = new AdminServiceProvider($this->container, $this->hooks, $this->pluginFile);
        $this->assertInstanceOf(AdminServiceProvider::class, $provider);
    }

    public function testProviderImplementsServiceProviderInterface(): void
    {
        $provider = new AdminServiceProvider($this->container, $this->hooks, $this->pluginFile);
        $this->assertInstanceOf(\AIAgent\Infrastructure\ServiceProviderInterface::class, $provider);
    }

    public function testProviderHasRegisterMethod(): void
    {
        $provider = new AdminServiceProvider($this->container, $this->hooks, $this->pluginFile);
        $this->assertTrue(method_exists($provider, 'register'));
    }

    public function testRegisterMethodDoesNotThrowException(): void
    {
        $provider = new AdminServiceProvider($this->container, $this->hooks, $this->pluginFile);
        
        $this->expectNotToPerformAssertions();
        $provider->register();
    }
}
