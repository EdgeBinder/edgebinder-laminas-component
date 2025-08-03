# EdgeBinder Laminas Component

[![Tests](https://github.com/EdgeBinder/edgebinder-component/workflows/Tests/badge.svg)](https://github.com/EdgeBinder/edgebinder-component/actions?query=workflow%3ATests)
[![Lint](https://github.com/EdgeBinder/edgebinder-component/workflows/Lint/badge.svg)](https://github.com/EdgeBinder/edgebinder-component/actions?query=workflow%3ALint)
[![Coverage Status](https://codecov.io/gh/EdgeBinder/edgebinder-component/branch/main/graph/badge.svg)](https://codecov.io/gh/EdgeBinder/edgebinder-component)
[![Latest Stable Version](https://poser.pugx.org/edgebinder/laminas-component/v/stable)](https://packagist.org/packages/edgebinder/laminas-component)
[![Total Downloads](https://poser.pugx.org/edgebinder/laminas-component/downloads)](https://packagist.org/packages/edgebinder/laminas-component)
[![License](https://poser.pugx.org/edgebinder/laminas-component/license)](https://packagist.org/packages/edgebinder/laminas-component)
[![PHP Version Require](https://poser.pugx.org/edgebinder/laminas-component/require/php)](https://packagist.org/packages/edgebinder/laminas-component)

A Laminas/Mezzio integration component for [EdgeBinder](https://github.com/EdgeBinder/edgebinder) - A lightweight, storage-agnostic relationship management library for PHP 8.3+.

## Features

- 🏭 **Service Factories** - Ready-to-use factories for EdgeBinder instances
- 🔧 **ConfigProvider** - Seamless Laminas/Mezzio integration
- 🎯 **Multi-Instance Support** - Multiple EdgeBinder instances with different adapters
- 🔌 **Extensible Adapters** - Framework-agnostic plugin architecture
- 🛡️ **Type Safety** - Full PHP 8.3+ type safety with PHPStan level 8
- ⚡ **Cross-Version Compatible** - Supports ServiceManager 3.x/4.x and PSR-11 v1/v2

## Requirements

- PHP 8.3+
- Laminas ServiceManager 3.0+ or 4.0+
- EdgeBinder ^0.2.0 (includes InMemoryAdapter for testing)

## Installation

Install via Composer:

```bash
composer require edgebinder/laminas-component
```

## Quick Start

### 1. Register the ConfigProvider

Add the ConfigProvider to your Laminas/Mezzio application:

```php
// config/config.php
use EdgeBinder\Component\ConfigProvider;

$aggregator = new ConfigAggregator([
    ConfigProvider::class,
    // ... other config providers
]);
```

### 2. Configure EdgeBinder

Copy the configuration template and customize it:

```bash
cp vendor/edgebinder/laminas-component/config/edgebinder.global.php.dist config/autoload/edgebinder.local.php
```

Or create configuration file manually:

```php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        // For testing and development
        'adapter' => 'inmemory',

        // For production with Weaviate (requires edgebinder/weaviate-adapter)
        // 'adapter' => 'weaviate',
        // 'weaviate_client' => 'weaviate.client.default',
        // 'collection_name' => 'EdgeBindings',
        // 'schema' => [
        //     'auto_create' => true,
        //     'vectorizer' => 'text2vec-openai',
        // ],
    ],
];
```

## Adapters

EdgeBinder uses a self-determining adapter architecture. Adapters register themselves with the EdgeBinder AdapterRegistry when their packages are loaded.

### Built-in Adapters

- **InMemoryAdapter** (`inmemory`) - Included with EdgeBinder core v0.2.0+, perfect for testing and development

### Available Adapters

- **WeaviateAdapter** (`weaviate`) - Install `edgebinder/weaviate-adapter` for vector database support
- **JanusAdapter** (`janus`) - Install `edgebinder/janus-adapter` for graph database support
- **RedisAdapter** (`redis`) - Install `edgebinder/redis-adapter` for caching and fast lookups

### 3. Use in Your Services

```php
use EdgeBinder\EdgeBinder;
use Psr\Container\ContainerInterface;

class MyService
{
    public function __construct(private EdgeBinder $edgeBinder) {}
    
    public function createRelationship($document, $knowledgeBase): void
    {
        $this->edgeBinder->bind(
            from: $document,
            to: $knowledgeBase,
            type: 'belongs_to',
            metadata: [
                'relevance_score' => 0.95,
                'semantic_similarity' => 0.87,
            ]
        );
    }
}

// Factory
class MyServiceFactory
{
    public function __invoke(ContainerInterface $container): MyService
    {
        return new MyService($container->get(EdgeBinder::class));
    }
}
```

## Multiple Instances

Configure multiple EdgeBinder instances for different use cases:

```php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        // RAG system with vector search
        'rag' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.rag',
            'collection_name' => 'RAGBindings',
            'schema' => ['vectorizer' => 'text2vec-openai'],
        ],

        // Analytics with graph database
        'analytics' => [
            'adapter' => 'janus',
            'janus_client' => 'janus.client.analytics',
            'graph_name' => 'AnalyticsGraph',
        ],

        // Fast cache lookups
        'cache' => [
            'adapter' => 'redis',
            'redis_client' => 'redis.client.cache',
            'ttl' => 3600,
        ],

        // Testing instance
        'test' => [
            'adapter' => 'inmemory',
        ],
    ],
];
```

Register named instances in your container:

```php
// config/autoload/dependencies.local.php
return [
    'dependencies' => [
        'factories' => [
            'edgebinder.rag' => function(ContainerInterface $container) {
                $factory = new EdgeBinderFactory();
                return $factory->createEdgeBinder($container, 'rag');
            },
            'edgebinder.analytics' => function(ContainerInterface $container) {
                $factory = new EdgeBinderFactory();
                return $factory->createEdgeBinder($container, 'analytics');
            },
            'edgebinder.test' => function(ContainerInterface $container) {
                $factory = new EdgeBinderFactory();
                return $factory->createEdgeBinder($container, 'test');
            },
        ],
    ],
];
        'rag' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.rag',
            'collection_name' => 'RAGBindings',
        ],
        'analytics' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.analytics',
            'collection_name' => 'AnalyticsBindings',
        ],
    ],
];
```

Use named instances:

```php
$ragBinder = $container->get('edgebinder.rag');
$analyticsBinder = $container->get('edgebinder.analytics');
```

## Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [Configuration Reference](docs/CONFIGURATION.md)
- [Usage Examples](docs/EXAMPLES.md)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This project is licensed under the Apache 2.0 License - see the [LICENSE](LICENSE) file for details.

## Support

- [GitHub Issues](https://github.com/EdgeBinder/edgebinder-component/issues)
- [Documentation](https://edgebinder.dev/docs)
- [Community Discussions](https://github.com/EdgeBinder/edgebinder-component/discussions)
