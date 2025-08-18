<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Factory;

use EdgeBinder\Component\Exception\ConfigurationException;
use EdgeBinder\Contracts\PersistenceAdapterInterface;
use EdgeBinder\EdgeBinder;
use EdgeBinder\Registry\AdapterConfiguration;
use EdgeBinder\Registry\AdapterRegistry;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating EdgeBinder instances with support for multiple named configurations.
 *
 * This factory supports:
 * - Single instance creation (default)
 * - Multiple named instances
 * - Framework-agnostic adapter discovery via EdgeBinder registry
 * - Container-based adapter factories
 *
 * Adapters must be registered with the EdgeBinder AdapterRegistry or available
 * as container services. No built-in adapters are provided - all adapters are
 * self-determining through the registry system.
 */
final class EdgeBinderFactory implements FactoryInterface
{
    /**
     * Create an EdgeBinder instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<string, mixed>|null $options
     *
     * @return EdgeBinder
     *
     * @throws ConfigurationException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EdgeBinder
    {
        return $this->createEdgeBinder($container, 'default');
    }

    /**
     * Create an EdgeBinder instance with the specified configuration name.
     *
     * @param ContainerInterface $container
     * @param string $name Configuration name (e.g., 'default', 'rag', 'analytics')
     *
     * @return EdgeBinder
     *
     * @throws ConfigurationException
     */
    public function createEdgeBinder(ContainerInterface $container, string $name = 'default'): EdgeBinder
    {
        $config = $this->getConfiguration($container);
        $instanceConfig = $this->getInstanceConfiguration($config, $name);

        // Get the appropriate adapter
        $adapter = $this->createAdapter($container, $instanceConfig, $config);

        return new EdgeBinder($adapter);
    }

    /**
     * Get the EdgeBinder configuration from the container.
     *
     * @param ContainerInterface $container
     *
     * @return array<string, mixed>
     *
     * @throws ConfigurationException
     */
    private function getConfiguration(ContainerInterface $container): array
    {
        if (!$container->has('config')) {
            throw ConfigurationException::missingConfiguration('config service not found in container');
        }

        $config = $container->get('config');

        if (!is_array($config)) {
            throw ConfigurationException::invalidConfiguration('config service must return an array');
        }

        return $config['edgebinder'] ?? [];
    }

    /**
     * Get the configuration for a specific instance.
     *
     * @param array<string, mixed> $config
     * @param string $name
     *
     * @return array<string, mixed>
     *
     * @throws ConfigurationException
     */
    private function getInstanceConfiguration(array $config, string $name): array
    {
        // Support for multiple named instances (flatter structure)
        if (isset($config[$name])) {
            return $config[$name];
        }

        // Backward compatibility: use root config as default
        if ($name === 'default' && isset($config['adapter'])) {
            return $config;
        }

        throw ConfigurationException::instanceNotConfigured($name);
    }

    /**
     * Create the appropriate adapter for the EdgeBinder instance.
     *
     * @param ContainerInterface $container
     * @param array<string, mixed> $instanceConfig
     * @param array<string, mixed> $globalConfig
     *
     * @return PersistenceAdapterInterface
     *
     * @throws ConfigurationException
     */
    private function createAdapter(
        ContainerInterface $container,
        array $instanceConfig,
        array $globalConfig
    ): PersistenceAdapterInterface {
        if (!isset($instanceConfig['adapter'])) {
            throw ConfigurationException::missingAdapter();
        }

        $adapterType = $instanceConfig['adapter'];

        // Method 1: Check EdgeBinder registry (framework-agnostic)
        if (AdapterRegistry::hasAdapter($adapterType)) {
            $config = $this->buildAdapterConfig($instanceConfig, $globalConfig, $container);

            return AdapterRegistry::create($adapterType, $config);
        }

        // Method 2: Check container for adapter factory service (framework-specific)
        $factoryServiceName = "edgebinder.adapter.{$adapterType}";
        if ($container->has($factoryServiceName)) {
            $factory = $container->get($factoryServiceName);
            $config = $this->buildAdapterConfig($instanceConfig, $globalConfig, $container);

            // Support different factory patterns
            if (is_callable($factory)) {
                $result = $factory($config);
                if ($result instanceof PersistenceAdapterInterface) {
                    return $result;
                }
            }

            if (is_object($factory) && method_exists($factory, 'createAdapter')) {
                $result = $factory->createAdapter($config);
                if ($result instanceof PersistenceAdapterInterface) {
                    return $result;
                }
            }

            if (is_object($factory) && method_exists($factory, '__invoke')) {
                $result = $factory($config);
                if ($result instanceof PersistenceAdapterInterface) {
                    return $result;
                }
            }
        }

        // No adapter found
        throw ConfigurationException::unsupportedAdapter($adapterType);
    }

    /**
     * Build standardized adapter configuration.
     *
     * @param array<string, mixed> $instanceConfig
     * @param array<string, mixed> $globalConfig
     * @param ContainerInterface $container
     *
     * @return AdapterConfiguration
     */
    private function buildAdapterConfig(
        array $instanceConfig,
        array $globalConfig,
        ContainerInterface $container
    ): AdapterConfiguration {
        return new AdapterConfiguration(
            $instanceConfig,
            $globalConfig,
            $container
        );
    }
}
