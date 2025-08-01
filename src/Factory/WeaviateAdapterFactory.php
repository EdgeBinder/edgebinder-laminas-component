<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Factory;

use EdgeBinder\Contracts\PersistenceAdapterInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating WeaviateAdapter instances with multiple connection support.
 */
final class WeaviateAdapterFactory
{
    public function __invoke(ContainerInterface $container): PersistenceAdapterInterface
    {
        return $this->createAdapter($container, 'default');
    }

    public function createAdapter(ContainerInterface $container, string $connectionName = 'default'): PersistenceAdapterInterface
    {
        // This method maintained for backward compatibility
        $config = $container->get('config')['edgebinder'] ?? [];
        $instanceConfig = $config[$connectionName] ?? $config;

        return $this->createAdapterFromConfig($container, $instanceConfig);
    }

    /**
     * @param array<string, mixed> $instanceConfig
     */
    public function createAdapterFromConfig(ContainerInterface $container, array $instanceConfig): PersistenceAdapterInterface
    {
        // Get the Weaviate client (assumes weaviate-client-component is installed)
        $clientServiceName = $instanceConfig['weaviate_client'] ?? 'weaviate.client.default';

        if (!$container->has($clientServiceName)) {
            throw new InvalidArgumentException("Weaviate client service '{$clientServiceName}' not found in container");
        }

        $weaviateClient = $container->get($clientServiceName);

        // Build adapter configuration from flatter structure
        $adapterConfig = [
            'collection_name' => $instanceConfig['collection_name'] ?? 'EdgeBindings',
            'schema' => $instanceConfig['schema'] ?? ['auto_create' => true],
        ];

        // For now, we'll throw an exception since the actual WeaviateAdapter class
        // would come from the edgebinder/weaviate-adapter package
        throw new InvalidArgumentException(
            'WeaviateAdapter creation requires the edgebinder/weaviate-adapter package to be installed'
        );
    }
}
