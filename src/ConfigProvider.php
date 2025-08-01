<?php

declare(strict_types=1);

namespace EdgeBinder\Component;

use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\EdgeBinder;

/**
 * Configuration provider for EdgeBinder Laminas component.
 *
 * Registers all necessary services and factories for EdgeBinder integration
 * with Laminas/Mezzio applications.
 */
final class ConfigProvider
{
    /**
     * Returns the configuration array for EdgeBinder component.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'edgebinder' => $this->getEdgeBinderConfig(),
        ];
    }

    /**
     * Returns the dependency configuration for service manager.
     *
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Main EdgeBinder service
                EdgeBinder::class => EdgeBinderFactory::class,

                // Named EdgeBinder instances
                'edgebinder.default' => EdgeBinderFactory::class,
            ],
            'aliases' => [
                'EdgeBinder' => EdgeBinder::class,
                'edgebinder' => 'edgebinder.default',
            ],
        ];
    }

    /**
     * Returns the default EdgeBinder configuration.
     *
     * @return array<string, mixed>
     */
    public function getEdgeBinderConfig(): array
    {
        return [
            // Default configuration can be overridden by local config
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.default',
            'collection_name' => 'EdgeBindings',
            'schema' => [
                'auto_create' => true,
            ],
        ];
    }
}
