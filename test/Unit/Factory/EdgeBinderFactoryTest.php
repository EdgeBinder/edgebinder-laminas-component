<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test\Factory;

use EdgeBinder\Component\Exception\ConfigurationException;
use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\EdgeBinder;
use EdgeBinder\Persistence\InMemory\InMemoryAdapterFactory;
use EdgeBinder\Registry\AdapterRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Test case for EdgeBinderFactory class.
 */
#[CoversClass(EdgeBinderFactory::class)]
final class EdgeBinderFactoryTest extends TestCase
{
    private EdgeBinderFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new EdgeBinderFactory();

        // Clear registry and register InMemoryAdapter for tests
        AdapterRegistry::clear();
        AdapterRegistry::register(new InMemoryAdapterFactory());
    }

    protected function tearDown(): void
    {
        // Clean up registry after each test
        AdapterRegistry::clear();
    }

    public function testInvokeCreatesEdgeBinderWithDefaultConfiguration(): void
    {
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

        $result = ($this->factory)($container, EdgeBinder::class);

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testCreateEdgeBinderWithNamedInstance(): void
    {
        $config = [
            'edgebinder' => [
                'rag' => [
                    'adapter' => 'inmemory',
                ],
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

        $result = $this->factory->createEdgeBinder($container, 'rag');

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testCreateEdgeBinderWithBackwardCompatibleConfiguration(): void
    {
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

        $result = $this->factory->createEdgeBinder($container, 'default');

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testThrowsExceptionWhenConfigServiceNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder configuration is missing: config service not found in container');

        ($this->factory)($container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenConfigServiceReturnsNonArray(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container
            ->method('get')
            ->with('config')
            ->willReturn('invalid-config');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder configuration is invalid: config service must return an array');

        ($this->factory)($container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenInstanceNotConfigured(): void
    {
        $config = [
            'edgebinder' => [
                'default' => [
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

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder instance "nonexistent" is not configured');

        $this->factory->createEdgeBinder($container, 'nonexistent');
    }

    public function testThrowsExceptionForUnsupportedAdapter(): void
    {
        // Clear registry to ensure unsupported adapter isn't available
        AdapterRegistry::clear();

        $config = [
            'edgebinder' => [
                'adapter' => 'unsupported',
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['edgebinder.adapter.unsupported', false],
            ]);

        $container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported adapter type "unsupported"');

        ($this->factory)($container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenAdapterConfigurationMissing(): void
    {
        $config = [
            'edgebinder' => [
                'default' => [
                    // Missing 'adapter' key
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

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Adapter type is required in configuration');

        $this->factory->createEdgeBinder($container, 'default');
    }
}
