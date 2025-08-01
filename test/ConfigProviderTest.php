<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test;

use EdgeBinder\Component\ConfigProvider;
use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\Component\Factory\WeaviateAdapterFactory;
use EdgeBinder\EdgeBinder;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ConfigProvider class.
 *
 * @covers \EdgeBinder\Component\ConfigProvider
 */
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    public function testInvokeReturnsExpectedStructure(): void
    {
        $config = ($this->configProvider)();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('edgebinder', $config);
    }

    public function testGetDependenciesReturnsExpectedFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);

        $factories = $dependencies['factories'];

        // Test main EdgeBinder service factory
        $this->assertArrayHasKey(EdgeBinder::class, $factories);
        $this->assertSame(EdgeBinderFactory::class, $factories[EdgeBinder::class]);

        // Test named instance factory
        $this->assertArrayHasKey('edgebinder.default', $factories);
        $this->assertSame(EdgeBinderFactory::class, $factories['edgebinder.default']);

        // Test adapter factory
        $this->assertArrayHasKey(WeaviateAdapterFactory::class, $factories);
        $this->assertIsCallable($factories[WeaviateAdapterFactory::class]);

        // Test adapter service factory
        $this->assertArrayHasKey('edgebinder.adapter.weaviate.default', $factories);
        $this->assertSame(WeaviateAdapterFactory::class, $factories['edgebinder.adapter.weaviate.default']);
    }

    public function testGetDependenciesReturnsExpectedAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();
        $aliases = $dependencies['aliases'];

        $this->assertArrayHasKey('EdgeBinder', $aliases);
        $this->assertSame(EdgeBinder::class, $aliases['EdgeBinder']);

        $this->assertArrayHasKey('edgebinder', $aliases);
        $this->assertSame('edgebinder.default', $aliases['edgebinder']);
    }

    public function testGetEdgeBinderConfigReturnsDefaultConfiguration(): void
    {
        $config = $this->configProvider->getEdgeBinderConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('adapter', $config);
        $this->assertSame('weaviate', $config['adapter']);

        $this->assertArrayHasKey('weaviate_client', $config);
        $this->assertSame('weaviate.client.default', $config['weaviate_client']);

        $this->assertArrayHasKey('collection_name', $config);
        $this->assertSame('EdgeBindings', $config['collection_name']);

        $this->assertArrayHasKey('schema', $config);
        $this->assertIsArray($config['schema']);
        $this->assertArrayHasKey('auto_create', $config['schema']);
        $this->assertTrue($config['schema']['auto_create']);
    }

    public function testWeaviateAdapterFactoryCallableReturnsCorrectInstance(): void
    {
        $dependencies = $this->configProvider->getDependencies();
        $factories = $dependencies['factories'];

        $factoryCallable = $factories[WeaviateAdapterFactory::class];
        $this->assertIsCallable($factoryCallable);

        $factory = $factoryCallable();
        $this->assertInstanceOf(WeaviateAdapterFactory::class, $factory);
    }

    public function testConfigurationStructureIsComplete(): void
    {
        $config = ($this->configProvider)();

        // Verify the complete structure matches expected format
        $expectedKeys = ['dependencies', 'edgebinder'];
        $this->assertSame($expectedKeys, array_keys($config));

        $dependencies = $config['dependencies'];
        $expectedDependencyKeys = ['factories', 'aliases'];
        $this->assertSame($expectedDependencyKeys, array_keys($dependencies));

        $edgeBinderConfig = $config['edgebinder'];
        $expectedConfigKeys = ['adapter', 'weaviate_client', 'collection_name', 'schema'];
        $this->assertSame($expectedConfigKeys, array_keys($edgeBinderConfig));
    }
}
