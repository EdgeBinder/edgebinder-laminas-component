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
 * Integration test for EdgeBinder component with InMemoryAdapter.
 */
final class EdgeBinderIntegrationTest extends TestCase
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

    public function testEdgeBinderCreationWithInMemoryAdapter(): void
    {
        $config = [
            'edgebinder' => [
                'adapter' => 'inmemory',
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new EdgeBinderFactory();
        $edgeBinder = ($factory)($container, EdgeBinder::class);

        $this->assertInstanceOf(EdgeBinder::class, $edgeBinder);
    }

    public function testNamedInstanceCreationWithInMemoryAdapter(): void
    {
        $config = [
            'edgebinder' => [
                'test_instance' => [
                    'adapter' => 'inmemory',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new EdgeBinderFactory();
        $edgeBinder = $factory->createEdgeBinder($container, 'test_instance');

        $this->assertInstanceOf(EdgeBinder::class, $edgeBinder);
    }
}
