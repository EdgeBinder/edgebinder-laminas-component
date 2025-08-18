<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test\Integration;

use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\EdgeBinder;
use EdgeBinder\Persistence\InMemory\InMemoryAdapterFactory;
use EdgeBinder\Registry\AdapterRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Integration test for EdgeBinder Core v0.9.0+ compatibility.
 *
 * Verifies that the EdgeBinder Laminas Component correctly creates
 * AdapterConfiguration objects when working with EdgeBinder Core v0.9.0+,
 * ensuring no TypeError exceptions occur during service container resolution.
 */
final class EdgeBinderCoreCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear registry and register InMemoryAdapter for tests
        AdapterRegistry::clear();
        AdapterRegistry::register(new InMemoryAdapterFactory());
    }

    protected function tearDown(): void
    {
        // Clean up registry after each test
        AdapterRegistry::clear();
    }

    public function testAdapterConfigurationObjectCreationWithCoreV9(): void
    {
        // Test configuration that would previously cause TypeError
        $config = [
            'edgebinder' => [
                'adapter' => 'inmemory',
                'config' => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnMap([
                ['config', true],
            ]);

        $container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new EdgeBinderFactory();

        // This should create AdapterConfiguration objects and pass them to AdapterRegistry::create()
        // Previously would throw: TypeError: EdgeBinder\Registry\AdapterRegistry::create():
        // Argument #2 ($config) must be of type EdgeBinder\Registry\AdapterConfiguration, array given
        $result = $factory->createEdgeBinder($container, 'default');

        // Verify EdgeBinder was created successfully
        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testServiceContainerInvocation(): void
    {
        // Test the __invoke method as well (service container pattern)
        $config = [
            'edgebinder' => [
                'adapter' => 'inmemory',
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnMap([
                ['config', true],
            ]);

        $container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new EdgeBinderFactory();

        // This simulates how Laminas ServiceManager would call the factory
        $result = ($factory)($container, EdgeBinder::class);

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }
}
