<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test;

use EdgeBinder\Component\ConfigProvider;
use EdgeBinder\Component\Factory\EdgeBinderFactory;
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
        $this->assertArrayNotHasKey('edgebinder', $config);
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

    public function testConfigurationStructureIsComplete(): void
    {
        $config = ($this->configProvider)();

        // Verify the complete structure matches expected format
        $expectedKeys = ['dependencies'];
        $this->assertSame($expectedKeys, array_keys($config));

        $dependencies = $config['dependencies'];
        $expectedDependencyKeys = ['factories', 'aliases'];
        $this->assertSame($expectedDependencyKeys, array_keys($dependencies));
    }
}
