# EdgeBinder Laminas Component

[![Build Status](https://github.com/EdgeBinder/edgebinder-component/workflows/Tests/badge.svg)](https://github.com/EdgeBinder/edgebinder-component/actions)
[![Code Quality](https://github.com/EdgeBinder/edgebinder-component/workflows/Code%20Quality/badge.svg)](https://github.com/EdgeBinder/edgebinder-component/actions)
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
- EdgeBinder ^0.1.0
- EdgeBinder Weaviate Adapter ^0.1.0

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

Create configuration file:

```php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        'adapter' => 'weaviate',
        'weaviate_client' => 'weaviate.client.default',
        'collection_name' => 'EdgeBindings',
        'schema' => [
            'auto_create' => true,
            'vectorizer' => 'text2vec-openai',
        ],
    ],
];
```

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
