<?php

declare(strict_types=1);

namespace EdgeBinder\Component;

use EdgeBinder\Component\Factory\EdgeBinderFactory;
use EdgeBinder\EdgeBinder;

/**
 * Configuration provider for EdgeBinder Laminas component.
 *
 * Registers EdgeBinder services and factories for Laminas/Mezzio applications.
 * Adapters are self-determining and register themselves through the EdgeBinder
 * AdapterRegistry system.
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
}
