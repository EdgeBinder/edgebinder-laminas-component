<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test\Factory;

use EdgeBinder\Component\Exception\ConfigurationException;
use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\Component\Factory\WeaviateAdapterFactory;
use EdgeBinder\EdgeBinder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Test case for EdgeBinderFactory class.
 * 
 * @covers \EdgeBinder\Component\Factory\EdgeBinderFactory
 */
final class EdgeBinderFactoryTest extends TestCase
{
    private EdgeBinderFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->factory = new EdgeBinderFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testInvokeCreatesEdgeBinderWithDefaultConfiguration(): void
    {
        $config = [
            'edgebinder' => [
                'adapter' => 'weaviate',
                'weaviate_client' => 'weaviate.client.default',
                'collection_name' => 'EdgeBindings',
                'schema' => ['auto_create' => true],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [WeaviateAdapterFactory::class, true],
            ]);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [WeaviateAdapterFactory::class, $this->createMockWeaviateAdapterFactory()],
            ]);

        $result = ($this->factory)($this->container, EdgeBinder::class);

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testCreateEdgeBinderWithNamedInstance(): void
    {
        $config = [
            'edgebinder' => [
                'rag' => [
                    'adapter' => 'weaviate',
                    'weaviate_client' => 'weaviate.client.rag',
                    'collection_name' => 'RAGBindings',
                    'schema' => ['auto_create' => true],
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [WeaviateAdapterFactory::class, true],
            ]);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [WeaviateAdapterFactory::class, $this->createMockWeaviateAdapterFactory()],
            ]);

        $result = $this->factory->createEdgeBinder($this->container, 'rag');

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testCreateEdgeBinderWithBackwardCompatibleConfiguration(): void
    {
        $config = [
            'edgebinder' => [
                'adapter' => 'weaviate',
                'weaviate_client' => 'weaviate.client.default',
                'collection_name' => 'EdgeBindings',
                'schema' => ['auto_create' => true],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [WeaviateAdapterFactory::class, true],
            ]);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [WeaviateAdapterFactory::class, $this->createMockWeaviateAdapterFactory()],
            ]);

        $result = $this->factory->createEdgeBinder($this->container, 'default');

        $this->assertInstanceOf(EdgeBinder::class, $result);
    }

    public function testThrowsExceptionWhenConfigServiceNotFound(): void
    {
        $this->container
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder configuration is missing: config service not found in container');

        ($this->factory)($this->container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenConfigServiceReturnsNonArray(): void
    {
        $this->container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->method('get')
            ->with('config')
            ->willReturn('invalid-config');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder configuration is invalid: config service must return an array');

        ($this->factory)($this->container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenInstanceNotConfigured(): void
    {
        $config = [
            'edgebinder' => [
                'default' => [
                    'adapter' => 'weaviate',
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EdgeBinder instance "nonexistent" is not configured');

        $this->factory->createEdgeBinder($this->container, 'nonexistent');
    }

    public function testThrowsExceptionForUnsupportedAdapter(): void
    {
        $config = [
            'edgebinder' => [
                'adapter' => 'unsupported',
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['edgebinder.adapter.unsupported', false],
            ]);

        $this->container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported adapter type "unsupported"');

        ($this->factory)($this->container, EdgeBinder::class);
    }

    public function testThrowsExceptionWhenWeaviateAdapterFactoryNotFound(): void
    {
        $config = [
            'edgebinder' => [
                'adapter' => 'weaviate',
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['edgebinder.adapter.weaviate', false],
                [WeaviateAdapterFactory::class, false],
            ]);

        $this->container
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Required service "EdgeBinder\Component\Factory\WeaviateAdapterFactory" is not registered');

        ($this->factory)($this->container, EdgeBinder::class);
    }

    private function createMockWeaviateAdapterFactory(): WeaviateAdapterFactory
    {
        $factory = $this->createMock(WeaviateAdapterFactory::class);
        $adapter = $this->createMock(\EdgeBinder\Contracts\PersistenceAdapterInterface::class);
        
        $factory
            ->method('createAdapterFromConfig')
            ->willReturn($adapter);

        return $factory;
    }
}
