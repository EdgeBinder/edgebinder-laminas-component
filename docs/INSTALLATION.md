# Installation Guide

This guide covers the installation and setup of the EdgeBinder Laminas Component.

## Requirements

- PHP 8.3 or higher
- Laminas ServiceManager 3.0+ or 4.0+
- EdgeBinder ^0.1.0
- EdgeBinder Weaviate Adapter ^0.1.0

## Installation

### 1. Install via Composer

```bash
composer require edgebinder/laminas-component
```

This will automatically install the required dependencies:
- `edgebinder/edgebinder`
- `edgebinder/weaviate-adapter`
- `laminas/laminas-servicemanager`
- `psr/container`

### 2. Register the ConfigProvider

#### For Laminas MVC Applications

Add the ConfigProvider to your `config/modules.config.php`:

```php
<?php
return [
    'Laminas\Router',
    'Laminas\Validator',
    // ... other modules
    'EdgeBinder\Component',
];
```

Or register it in your `Module.php`:

```php
<?php
namespace Application;

use EdgeBinder\Component\ConfigProvider as EdgeBinderConfigProvider;

class Module
{
    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php',
            (new EdgeBinderConfigProvider())()
        );
    }
}
```

#### For Mezzio Applications

Add the ConfigProvider to your `config/config.php`:

```php
<?php
use Laminas\ConfigAggregator\ConfigAggregator;
use EdgeBinder\Component\ConfigProvider;

$aggregator = new ConfigAggregator([
    ConfigProvider::class,
    // ... other config providers
]);

return $aggregator->getMergedConfig();
```

### 3. Configure EdgeBinder

Copy the configuration template:

```bash
cp vendor/edgebinder/laminas-component/config/edgebinder.global.php.dist config/autoload/edgebinder.local.php
```

Edit the configuration file according to your needs:

```php
<?php
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

### 4. Configure Weaviate Client (Required)

Since EdgeBinder uses Weaviate as the default adapter, you need to configure a Weaviate client. 

If you're using the `zestic/weaviate-client-component`, add its ConfigProvider:

```php
<?php
use Laminas\ConfigAggregator\ConfigAggregator;
use EdgeBinder\Component\ConfigProvider;
use Zestic\WeaviateClient\ConfigProvider as WeaviateConfigProvider;

$aggregator = new ConfigAggregator([
    WeaviateConfigProvider::class,
    ConfigProvider::class,
    // ... other config providers
]);
```

And configure the Weaviate client:

```php
<?php
// config/autoload/weaviate.local.php
return [
    'weaviate' => [
        'default' => [
            'host' => 'http://localhost:8080',
            'api_key' => 'your-api-key', // Optional
        ],
    ],
];
```

## Verification

### 1. Test the Installation

Create a simple test script to verify the installation:

```php
<?php
// test-installation.php
require_once 'vendor/autoload.php';

use Laminas\ServiceManager\ServiceManager;
use EdgeBinder\Component\ConfigProvider;
use EdgeBinder\EdgeBinder;

// Create service manager with EdgeBinder configuration
$config = (new ConfigProvider())();
$serviceManager = new ServiceManager($config['dependencies']);
$serviceManager->setService('config', [
    'edgebinder' => [
        'adapter' => 'weaviate',
        'weaviate_client' => 'weaviate.client.default',
        'collection_name' => 'EdgeBindings',
        'schema' => ['auto_create' => true],
    ],
    // Add your Weaviate client configuration here
]);

try {
    $edgeBinder = $serviceManager->get(EdgeBinder::class);
    echo "✅ EdgeBinder successfully created!\n";
    echo "Adapter type: " . get_class($edgeBinder) . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

Run the test:

```bash
php test-installation.php
```

### 2. Run Unit Tests

If you want to run the component's tests:

```bash
cd vendor/edgebinder/laminas-component
composer install
composer test
```

## Troubleshooting

### Common Issues

1. **"Required service not found"**
   - Ensure all required services are registered in your container
   - Check that the Weaviate client component is properly configured

2. **"EdgeBinder configuration is missing"**
   - Verify that the configuration file is in the correct location
   - Check that the ConfigProvider is registered

3. **"Unsupported adapter type"**
   - Ensure the adapter is properly registered
   - For custom adapters, register them with the AdapterRegistry

### Getting Help

- [GitHub Issues](https://github.com/EdgeBinder/edgebinder-component/issues)
- [EdgeBinder Documentation](https://edgebinder.dev/docs)
- [Community Discussions](https://github.com/EdgeBinder/edgebinder-component/discussions)

## Next Steps

- [Configuration Reference](CONFIGURATION.md)
- [Usage Examples](EXAMPLES.md)
- [EdgeBinder Core Documentation](https://github.com/EdgeBinder/edgebinder)
