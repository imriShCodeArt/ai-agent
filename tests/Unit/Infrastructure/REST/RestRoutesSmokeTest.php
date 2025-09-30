<?php

namespace AIAgent\Tests\Unit\Infrastructure\REST;

use PHPUnit\Framework\TestCase;
use AIAgent\Application\Providers\RestApiServiceProvider;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Support\Logger;

final class RestRoutesSmokeTest extends TestCase
{
    public function testServiceProviderRegistersRoutes(): void
    {
        $container = new ServiceContainer();
        $container->singleton(Logger::class, fn() => new Logger());
        $provider = new RestApiServiceProvider($container);
        $provider->register();
        $this->assertTrue(is_callable([$provider, 'registerRestRoutes']));
        // Invoke to ensure no fatal due to permission_callback wiring
        $provider->registerRestRoutes();
    }
}


