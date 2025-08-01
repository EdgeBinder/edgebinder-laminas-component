<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Factory;

use EdgeBinder\Adapter\Weaviate\WeaviateAdapter;
use EdgeBinder\Component\Exception\ConfigurationException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Weaviate\WeaviateClient;

/**
 * Factory for creating WeaviateAdapter instances with multiple connection support.
 * 
 * This factory supports:
 * - Single connection creation (default)
 * - Multiple named connections
 * - Configuration validation
 * - Client service resolution from container
 */
final class WeaviateAdapterFactory implements FactoryInterface
{
    /**
     * Create a WeaviateAdapter instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<string, mixed>|null $options
     * @return WeaviateAdapter
     * @throws ConfigurationException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): WeaviateAdapter
    {
        return $this->createAdapter($container, 'default');
    }

    /**
     * Create a WeaviateAdapter with the specified connection name.
     *
     * @param ContainerInterface $container
     * @param string $connectionName
     * @return WeaviateAdapter
     * @throws ConfigurationException
     */
    public function createAdapter(ContainerInterface $container, string $connectionName = 'default'): WeaviateAdapter
    {
        // This method maintained for backward compatibility
        $config = $this->getConfiguration($container);
        $instanceConfig = $config[$connectionName] ?? $config;

        return $this->createAdapterFromConfig($container, $instanceConfig);
    }

    /**
     * Create a WeaviateAdapter from instance configuration.
     *
     * @param ContainerInterface $container
     * @param array<string, mixed> $instanceConfig
     * @return WeaviateAdapter
     * @throws ConfigurationException
     */
    public function createAdapterFromConfig(ContainerInterface $container, array $instanceConfig): WeaviateAdapter
    {
        // Get the Weaviate client (assumes weaviate-client-component is installed)
        $clientServiceName = $instanceConfig['weaviate_client'] ?? 'weaviate.client.default';
        
        if (!$container->has($clientServiceName)) {
            throw ConfigurationException::missingService($clientServiceName);
        }

        $weaviateClient = $container->get($clientServiceName);

        if (!$weaviateClient instanceof WeaviateClient) {
            throw ConfigurationException::invalidService(
                $clientServiceName,
                'must return WeaviateClient instance'
            );
        }

        // Build adapter configuration from flatter structure
        $adapterConfig = $this->buildAdapterConfiguration($instanceConfig);

        return new WeaviateAdapter($weaviateClient, $adapterConfig);
    }

    /**
     * Get the EdgeBinder configuration from the container.
     *
     * @param ContainerInterface $container
     * @return array<string, mixed>
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
     * Build adapter configuration from instance configuration.
     *
     * @param array<string, mixed> $instanceConfig
     * @return array<string, mixed>
     */
    private function buildAdapterConfiguration(array $instanceConfig): array
    {
        return [
            'collection_name' => $instanceConfig['collection_name'] ?? 'EdgeBindings',
            'schema' => $instanceConfig['schema'] ?? ['auto_create' => true],
        ];
    }
}
