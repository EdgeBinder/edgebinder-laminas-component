# EdgeBinder Component for Laminas

## Project Overview

This document outlines the plan to create `edgebinder/laminas-component`, a Laminas-specific integration library that provides factories, ConfigProvider, and supporting classes to seamlessly integrate EdgeBinder with multiple adapters into Laminas/Mezzio applications.

## Project Details

- **Project Name**: `edgebinder/laminas-component`
- **Package Name**: `edgebinder/laminas-component`
- **PHP Version**: 8.3+
- **License**: Apache 2.0
- **Dependencies**:
  - `edgebinder/edgebinder` ^0.1.0 (main dependency with adapter registry)
  - `edgebinder/weaviate-adapter` ^0.1.0 (Weaviate support)
  - `laminas/laminas-servicemanager` ^3.0 || ^4.0 (for factories and DI)
  - `psr/container` ^1.0 || ^2.0 (PSR-11 container interface)
- **Dev Dependencies**:
  - `phpunit/phpunit` (testing framework)
  - `phpstan/phpstan` (static analysis)
  - `squizlabs/php_codesniffer` (coding standards)
  - `ergebnis/composer-normalize` (composer.json formatting)

## Architecture Overview

The component will follow modern Laminas/Mezzio conventions and provide:

1. **ConfigProvider** - Main configuration provider for service registration
2. **Factories** - Service factories for creating EdgeBinder instances with different adapters
3. **Configuration Classes** - Typed configuration objects with PHP 8.3+ features
4. **Multi-Adapter Support** - Support for multiple adapter types and connections
5. **Extensible Adapter System** - Framework-agnostic plugin architecture via EdgeBinder registry
6. **Cross-Version Compatibility** - Support for Laminas ServiceManager 3.x and 4.x, PSR-11 v1 and v2
7. **Documentation** - Comprehensive usage examples focused on modern patterns

## Directory Structure

```
laminas-component/
├── .github/
│   └── workflows/
│       ├── tests.yml
│       └── lint.yml
├── composer.json
├── README.md
├── LICENSE
├── phpunit.xml
├── src/
│   ├── ConfigProvider.php
│   ├── Factory/
│   │   ├── EdgeBinderFactory.php
│   │   ├── EdgeBinderAbstractFactory.php
│   │   └── WeaviateAdapterFactory.php
│   ├── Configuration/
│   │   ├── EdgeBinderConfig.php
│   │   ├── AdapterConfig.php
│   │   └── WeaviateAdapterConfig.php
│   └── Exception/
│       └── ConfigurationException.php
├── config/
│   └── edgebinder.global.php.dist
├── test/
│   ├── ConfigProviderTest.php
│   ├── Integration/
│   │   ├── MultipleInstancesIntegrationTest.php
│   │   ├── WeaviateAdapterIntegrationTest.php
│   │   └── ConfigProviderIntegrationTest.php
│   ├── Factory/
│   │   ├── EdgeBinderFactoryTest.php
│   │   ├── EdgeBinderAbstractFactoryTest.php
│   │   └── WeaviateAdapterFactoryTest.php
│   └── Configuration/
│       ├── EdgeBinderConfigTest.php
│       └── WeaviateAdapterConfigTest.php
├── docker-compose.yml
└── docs/
    ├── INSTALLATION.md
    ├── CONFIGURATION.md
    └── EXAMPLES.md
```

## Core Components

### 1. ConfigProvider Class

The main configuration provider that registers all services:

```php
<?php
namespace EdgeBinder\Component;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'edgebinder' => $this->getEdgeBinderConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                \EdgeBinder\EdgeBinder::class => Factory\EdgeBinderFactory::class,
                
                // Named EdgeBinder instances
                'edgebinder.default' => Factory\EdgeBinderFactory::class,
                'edgebinder.rag' => [Factory\EdgeBinderFactory::class, 'rag'],
                'edgebinder.social' => [Factory\EdgeBinderFactory::class, 'social'],
                'edgebinder.analytics' => [Factory\EdgeBinderFactory::class, 'analytics'],
                
                // Built-in adapter factories
                'edgebinder.adapter.weaviate.default' => Factory\WeaviateAdapterFactory::class,
                'edgebinder.adapter.weaviate.rag' => [Factory\WeaviateAdapterFactory::class, 'rag'],
                'edgebinder.adapter.weaviate.analytics' => [Factory\WeaviateAdapterFactory::class, 'analytics'],
            ],
            'aliases' => [
                'EdgeBinder' => \EdgeBinder\EdgeBinder::class,
                'edgebinder' => 'edgebinder.default',
            ],
            'abstract_factories' => [
                Factory\EdgeBinderAbstractFactory::class,
            ],
        ];
    }
}
```

### 2. EdgeBinderFactory

Factory for creating EdgeBinder instances with support for multiple named configurations:

```php
<?php
namespace EdgeBinder\Component\Factory;

use Psr\Container\ContainerInterface;
use EdgeBinder\EdgeBinder;
use EdgeBinder\Contracts\PersistenceAdapterInterface;

class EdgeBinderFactory
{
    public function __invoke(ContainerInterface $container): EdgeBinder
    {
        return $this->createEdgeBinder($container, 'default');
    }

    public function createEdgeBinder(ContainerInterface $container, string $name = 'default'): EdgeBinder
    {
        $config = $container->get('config')['edgebinder'] ?? [];
        
        // Support for multiple named instances (flatter structure)
        if (isset($config[$name])) {
            $instanceConfig = $config[$name];
        } elseif ($name === 'default' && isset($config['adapter'])) {
            // Backward compatibility: use root config as default
            $instanceConfig = $config;
        } else {
            throw new \InvalidArgumentException("EdgeBinder instance '{$name}' not configured");
        }
        
        // Get the appropriate adapter
        $adapter = $this->createAdapter($container, $instanceConfig, $config);
        
        return new EdgeBinder($adapter);
    }

    private function createAdapter(
        ContainerInterface $container,
        array $instanceConfig,
        array $globalConfig
    ): PersistenceAdapterInterface {
        $adapterType = $instanceConfig['adapter'] ?? 'weaviate';

        // Method 1: Check EdgeBinder registry (framework-agnostic) ✅ IMPLEMENTED
        if (\EdgeBinder\Registry\AdapterRegistry::hasAdapter($adapterType)) {
            $config = $this->buildAdapterConfig($instanceConfig, $globalConfig, $container);
            return \EdgeBinder\Registry\AdapterRegistry::create($adapterType, $config);
        }

        // Method 2: Check container for adapter factory service (framework-specific)
        $factoryServiceName = "edgebinder.adapter.{$adapterType}";
        if ($container->has($factoryServiceName)) {
            $factory = $container->get($factoryServiceName);
            $config = $this->buildAdapterConfig($instanceConfig, $globalConfig, $container);

            // Support different factory patterns
            if (is_callable($factory)) {
                return $factory($config);
            }

            if (method_exists($factory, 'createAdapter')) {
                return $factory->createAdapter($config);
            }

            if (method_exists($factory, '__invoke')) {
                return $factory($config);
            }
        }

        // Method 3: Built-in adapters
        return match($adapterType) {
            'weaviate' => $this->createWeaviateAdapter($container, $instanceConfig, $globalConfig),
            default => throw new \InvalidArgumentException("Unsupported adapter type: {$adapterType}")
        };
    }

    private function buildAdapterConfig(array $instanceConfig, array $globalConfig, ContainerInterface $container): array
    {
        // Build standardized config that includes container access for third-party adapters
        // This format matches the implemented EdgeBinder configuration structure ✅
        return [
            'instance' => $instanceConfig,
            'global' => $globalConfig,
            'container' => $container,
        ];
    }

    private function createWeaviateAdapter(
        ContainerInterface $container,
        array $instanceConfig,
        array $globalConfig
    ): PersistenceAdapterInterface {
        $factory = $container->get(WeaviateAdapterFactory::class);
        return $factory->createAdapterFromConfig($container, $instanceConfig);
    }
}
```

### 3. WeaviateAdapterFactory

Factory for creating WeaviateAdapter instances with multiple connection support:

```php
<?php
namespace EdgeBinder\Component\Factory;

use Psr\Container\ContainerInterface;
use EdgeBinder\Adapter\Weaviate\WeaviateAdapter;
use Weaviate\WeaviateClient;

class WeaviateAdapterFactory
{
    public function __invoke(ContainerInterface $container): WeaviateAdapter
    {
        return $this->createAdapter($container, 'default');
    }

    public function createAdapter(ContainerInterface $container, string $connectionName = 'default'): WeaviateAdapter
    {
        // This method maintained for backward compatibility
        $config = $container->get('config')['edgebinder'] ?? [];
        $instanceConfig = $config[$connectionName] ?? $config;

        return $this->createAdapterFromConfig($container, $instanceConfig);
    }

    public function createAdapterFromConfig(ContainerInterface $container, array $instanceConfig): WeaviateAdapter
    {
        // Get the Weaviate client (assumes weaviate-client-component is installed)
        $clientServiceName = $instanceConfig['weaviate_client'] ?? 'weaviate.client.default';
        $weaviateClient = $container->get($clientServiceName);

        if (!$weaviateClient instanceof WeaviateClient) {
            throw new \InvalidArgumentException("Service '{$clientServiceName}' must return WeaviateClient instance");
        }

        // Build adapter configuration from flatter structure
        $adapterConfig = [
            'collection_name' => $instanceConfig['collection_name'] ?? 'EdgeBindings',
            'schema' => $instanceConfig['schema'] ?? ['auto_create' => true],
        ];

        return new WeaviateAdapter($weaviateClient, $adapterConfig);
    }
}
```

## Framework-Agnostic Extensible Adapter System ✅ IMPLEMENTED

The extensibility is handled through **EdgeBinder** rather than framework-specific components, making it work across all frameworks (Laminas, Symfony, Laravel, Slim, etc.). **This system has been fully implemented and is available in EdgeBinder v1.0+.**

### Core Extension Points (in edgebinder/edgebinder) ✅ IMPLEMENTED

#### 1. AdapterFactoryInterface (EdgeBinder) ✅ IMPLEMENTED

The interface is fully implemented in `src/Registry/AdapterFactoryInterface.php`:

```php
<?php
// edgebinder/edgebinder - src/Registry/AdapterFactoryInterface.php
namespace EdgeBinder\Registry;

use EdgeBinder\Contracts\PersistenceAdapterInterface;

interface AdapterFactoryInterface
{
    /**
     * Create adapter instance with configuration.
     *
     * @param array $config Configuration array containing:
     *                     - 'instance': instance-specific config
     *                     - 'global': global EdgeBinder config
     *                     - 'container': PSR-11 container for DI
     */
    public function createAdapter(array $config): PersistenceAdapterInterface;

    /**
     * Get the adapter type this factory handles.
     */
    public function getAdapterType(): string;
}
```

#### 2. AdapterRegistry (EdgeBinder) ✅ IMPLEMENTED

The registry is fully implemented in `src/Registry/AdapterRegistry.php`:

```php
<?php
// edgebinder/edgebinder - src/Registry/AdapterRegistry.php
namespace EdgeBinder\Registry;

class AdapterRegistry
{
    private static array $factories = [];

    /**
     * Register an adapter factory.
     */
    public static function register(AdapterFactoryInterface $factory): void
    {
        $type = $factory->getAdapterType();

        if (isset(self::$factories[$type])) {
            throw AdapterException::alreadyRegistered($type);
        }

        self::$factories[$type] = $factory;
    }

    /**
     * Create adapter instance.
     */
    public static function create(string $type, array $config): PersistenceAdapterInterface
    {
        if (!isset(self::$factories[$type])) {
            throw AdapterException::factoryNotFound($type, array_keys(self::$factories));
        }

        try {
            return self::$factories[$type]->createAdapter($config);
        } catch (\Throwable $e) {
            if ($e instanceof AdapterException) {
                throw $e;
            }
            throw AdapterException::creationFailed($type, $e->getMessage(), $e);
        }
    }

    /**
     * Check if adapter type is registered.
     */
    public static function hasAdapter(string $type): bool
    {
        return isset(self::$factories[$type]);
    }

    /**
     * Get all registered adapter types.
     */
    public static function getRegisteredTypes(): array
    {
        return array_keys(self::$factories);
    }
}
```

### Third-Party Adapter Implementation Example

#### Creating a Custom Janus Adapter

```php
<?php
// myvendor/janus-edgebinder-adapter
namespace MyVendor\JanusEdgeBinderAdapter;

use EdgeBinder\Registry\AdapterFactoryInterface;
use EdgeBinder\Contracts\PersistenceAdapterInterface;

class JanusAdapterFactory implements AdapterFactoryInterface
{
    public function createAdapter(array $config): PersistenceAdapterInterface
    {
        $container = $config['container'];
        $instanceConfig = $config['instance'];
        $globalConfig = $config['global'];

        // Get connection configuration
        $connectionName = $instanceConfig['connection'] ?? 'default';
        $janusConfig = $globalConfig['janus']['connections'][$connectionName] ?? [];

        // Get Janus client from container (framework-agnostic)
        $janusClient = $container->get($janusConfig['client'] ?? 'janus.client.default');

        return new JanusAdapter($janusClient, $janusConfig['config'] ?? []);
    }

    public function getAdapterType(): string
    {
        return 'janus';
    }
}
```

## Framework-Specific Registration Examples ✅ IMPLEMENTED

The beauty of this approach is that registration works the same way across all frameworks. **These patterns are now fully supported by the implemented extensible adapter system:**

### Laminas/Mezzio Registration

**Option A: Via ConfigProvider (Recommended)**
```php
<?php
namespace MyVendor\JanusEdgeBinderAdapter;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    JanusAdapterFactory::class => function() {
                        return new JanusAdapterFactory();
                    },
                ],
            ],
        ];
    }
}

// In Module.php or application bootstrap
public function onBootstrap($e)
{
    // Register with EdgeBinder registry
    \EdgeBinder\Registry\AdapterRegistry::register(new JanusAdapterFactory());
}
```

**Option B: Via Container Service (Alternative)**
```php
// In ConfigProvider
'dependencies' => [
    'factories' => [
        'edgebinder.adapter.janus' => function($container) {
            return new JanusAdapterFactory();
        },
    ],
],
```

### Symfony Registration

**Option A: Via Service Registration + Event Listener**
```yaml
# services.yaml
services:
    MyVendor\JanusEdgeBinderAdapter\JanusAdapterFactory:
        tags: ['edgebinder.adapter_factory']
```

```php
<?php
// In a compiler pass or event listener
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class EdgeBinderAdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('edgebinder.adapter_factory') as $id => $tags) {
            $factory = $container->get($id);
            \EdgeBinder\Registry\AdapterRegistry::register($factory);
        }
    }
}
```

**Option B: Via Bundle Boot Method**
```php
<?php
// In your bundle
public function boot()
{
    \EdgeBinder\Registry\AdapterRegistry::register(new JanusAdapterFactory());
}
```

### Laravel Registration

**Via Service Provider**
```php
<?php
namespace MyVendor\JanusEdgeBinderAdapter;

use Illuminate\Support\ServiceProvider;

class JanusAdapterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register with EdgeBinder registry
        \EdgeBinder\Registry\AdapterRegistry::register(new JanusAdapterFactory());
    }

    public function register()
    {
        // Optional: Register as container service too
        $this->app->singleton('edgebinder.adapter.janus', function() {
            return new JanusAdapterFactory();
        });
    }
}
```

### Slim Framework Registration

**Via Container Configuration**
```php
<?php
// In container configuration
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'edgebinder.adapter.janus' => function() {
        return new \MyVendor\JanusEdgeBinderAdapter\JanusAdapterFactory();
    },
]);

// After container is built
$container = $containerBuilder->build();

// Register with EdgeBinder
\EdgeBinder\Registry\AdapterRegistry::register(
    $container->get('edgebinder.adapter.janus')
);
```

### Generic PHP Registration

**For any PSR-11 compatible framework**
```php
<?php
// Anywhere in your application bootstrap
\EdgeBinder\Registry\AdapterRegistry::register(new JanusAdapterFactory());
```

## Universal Configuration Usage ✅ IMPLEMENTED

Once registered (regardless of framework), the custom adapter works identically across all frameworks. **This configuration format is now fully supported:**

```php
// config/autoload/edgebinder.local.php (Laminas)
// config/packages/edgebinder.yaml (Symfony)
// config/edgebinder.php (Laravel)
// Any framework configuration
return [
    'edgebinder' => [
        'social' => [
            'adapter' => 'janus',  // Custom adapter works everywhere
            'janus_client' => 'janus.client.social',
            'graph_name' => 'SocialNetwork',
            'consistency_level' => 'eventual',
        ],
    ],
];
```

## Benefits of This Framework-Agnostic Architecture

### 🌐 **Universal Compatibility** ✅ IMPLEMENTED
- ✅ **Same Interface**: Works identically across Laminas, Symfony, Laravel, Slim, etc.
- ✅ **No Framework Lock-in**: Adapters work everywhere without modification
- ✅ **PSR-11 Based**: Uses standard container interfaces

### 🏗️ **Clean Architecture** ✅ IMPLEMENTED
- ✅ **Core Extension Point**: Extensibility is in EdgeBinder, not framework components
- ✅ **No Component Modifications**: Add adapters without changing any framework components
- ✅ **Separation of Concerns**: Framework components handle DI, core handles adapter logic

### 📦 **Easy Distribution** ✅ IMPLEMENTED
- ✅ **Simple Packages**: Third-party adapters are just composer packages
- ✅ **Framework Agnostic**: One adapter package works with all frameworks
- ✅ **Standard Patterns**: Consistent registration patterns across frameworks

### 🔧 **Developer Experience** ✅ IMPLEMENTED
- ✅ **Multiple Discovery Methods**: Core registry + container services + built-in adapters
- ✅ **Configuration Consistency**: Same config patterns for all adapters
- ✅ **Type Safety**: Strong typing through EdgeBinder interfaces
- ✅ **Container Access**: Adapters can use dependency injection from any framework

## Configuration Examples

### Flatter Configuration Structure (Recommended)

```php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        // Direct instance configuration
        'default' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.default',
            'collection_name' => 'EdgeBindings',
            'schema' => ['auto_create' => true],
        ],
        'rag' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.rag',
            'collection_name' => 'RAGBindings',
            'schema' => [
                'auto_create' => true,
                'vectorizer' => 'text2vec-openai',
            ],
        ],
        'analytics' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.analytics',
            'collection_name' => 'AnalyticsBindings',
            'schema' => ['auto_create' => true],
        ],
    ],
];
```

### Basic Single Instance Configuration

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

### Nested Configuration Structure (Alternative)

For comparison, here's the more complex nested structure that was considered:

```php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        'instances' => [
            'default' => [
                'adapter' => 'weaviate',
                'connection' => 'default',
            ],
            'rag' => [
                'adapter' => 'weaviate',
                'connection' => 'rag_database',
            ],
        ],
        'adapters' => [
            'weaviate' => [
                'connections' => [
                    'default' => [
                        'client' => 'weaviate.client.default',
                        'config' => [
                            'collection_name' => 'EdgeBindings',
                            'schema' => ['auto_create' => true],
                        ],
                    ],
                    'rag_database' => [
                        'client' => 'weaviate.client.rag',
                        'config' => [
                            'collection_name' => 'RAGBindings',
                            'schema' => [
                                'auto_create' => true,
                                'vectorizer' => 'text2vec-openai',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

## Health Checks Explanation

Health checks provide monitoring capabilities for your EdgeBinder instances:

### What Health Checks Would Monitor:
1. **Adapter Connectivity**: Can EdgeBinder reach its storage backend?
2. **Schema Validation**: Are required collections/tables present?
3. **Performance Metrics**: Response times, connection pool status
4. **Configuration Validation**: Are all required services available?

### Example Health Check Implementation:
```php
class EdgeBinderHealthCheck
{
    public function check(EdgeBinder $edgeBinder): HealthStatus
    {
        try {
            // Test basic connectivity
            $testBinding = $edgeBinder->bind($testEntity1, $testEntity2, 'health_check');
            $found = $edgeBinder->findBinding($testBinding->getId());
            $edgeBinder->unbind($testBinding->getId());

            return HealthStatus::healthy([
                'adapter' => 'weaviate',
                'response_time' => $responseTime,
                'collections' => $collectionCount,
            ]);
        } catch (\Exception $e) {
            return HealthStatus::unhealthy($e->getMessage());
        }
    }
}
```

### Integration with Monitoring:
- **Laminas Health Check Module**: Integrate with existing health check systems
- **Prometheus Metrics**: Export metrics for monitoring dashboards
- **Log Aggregation**: Structured logging for health events
- **Alerting**: Notify when EdgeBinder instances become unhealthy

## Usage Examples

### Basic Usage in Controller

```php
<?php
namespace App\Handler;

use EdgeBinder\EdgeBinder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DocumentHandler
{
    public function __construct(
        #[Inject('edgebinder.rag')]
        private EdgeBinder $ragBinder,

        #[Inject('edgebinder.analytics')]
        private EdgeBinder $analyticsBinder
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Use RAG EdgeBinder for document relationships
        $this->ragBinder->bind(
            from: $document,
            to: $knowledgeBase,
            type: 'belongs_to',
            metadata: [
                'relevance_score' => 0.95,
                'semantic_similarity' => 0.87,
                'topics' => ['AI', 'machine-learning']
            ]
        );

        // Use analytics EdgeBinder for tracking relationships
        $this->analyticsBinder->bind(
            from: $user,
            to: $document,
            type: 'viewed',
            metadata: [
                'timestamp' => new DateTimeImmutable(),
                'session_id' => $sessionId,
                'duration' => 120
            ]
        );

        return new JsonResponse(['status' => 'success']);
    }
}
```

### Multiple Weaviate Databases

Perfect for your use case with RAG and different data separation:

```php
// Different EdgeBinder instances using different Weaviate databases
$ragBinder = $container->get('edgebinder.rag');        // Uses rag database
$analyticsBinder = $container->get('edgebinder.analytics'); // Uses analytics database

// RAG relationships in dedicated database
$ragBinder->bind($document, $knowledgeBase, 'semantic_similarity', [
    'embedding_model' => 'text-embedding-ada-002',
    'similarity_score' => 0.92,
    'vector_distance' => 0.08
]);

// Analytics relationships in separate database
$analyticsBinder->bind($user, $event, 'triggered', [
    'timestamp' => time(),
    'session_id' => $sessionId,
    'event_type' => 'page_view'
]);

// Query each database independently
$ragRelationships = $ragBinder->query()
    ->from($document)
    ->type('semantic_similarity')
    ->where('similarity_score', '>', 0.8)
    ->get();

$userAnalytics = $analyticsBinder->query()
    ->from($user)
    ->type('triggered')
    ->where('event_type', 'page_view')
    ->get();
```

### Service Factory Usage

```php
// Manual retrieval of specific instances
$defaultBinder = $container->get('edgebinder.default');
$ragBinder = $container->get('edgebinder.rag');
$analyticsBinder = $container->get('edgebinder.analytics');

// Or using the abstract factory pattern
$customBinder = $container->get('edgebinder.custom_instance');
```

## Implementation Plan

### Phase 1: Foundation & Core Structure (Week 1)
- [ ] Set up project structure and composer.json with cross-version compatibility
- [ ] Create GitHub repository with proper README and badges
- [ ] Set up GitHub Actions workflows (tests.yml, lint.yml) with ServiceManager 3.x/4.x matrix
- [ ] Configure PHPUnit, PHPStan, and coding standards
- [ ] Create basic ConfigProvider class
- [ ] Implement EdgeBinderFactory with single instance support
- [ ] Create basic configuration classes with PHP 8.3+ features
- [ ] Set up compatibility testing for Laminas ServiceManager 3.x and 4.x
- [ ] Set up compatibility testing for PSR-11 v1 and v2

### Phase 2: Multi-Instance Support & Configuration (Week 2)
- [ ] Implement WeaviateAdapterFactory with proper configuration handling
- [ ] Add support for multiple named EdgeBinder instances (`edgebinder.rag`, `edgebinder.analytics`)
- [ ] Create comprehensive configuration validation and error reporting
- [ ] Add exception handling for configuration errors with user-friendly messages
- [ ] Implement EdgeBinderAbstractFactory for dynamic instances
- [ ] Create configuration builders and helpers for complex scenarios
- [ ] Support both flat and nested configuration structures

### Phase 3: Testing Infrastructure & Quality Assurance (Week 3)
- [ ] Write comprehensive unit tests (90%+ coverage)
- [ ] Create integration tests with real Weaviate instances
- [ ] Test multiple instance scenarios with different databases
- [ ] Set up Docker Compose for integration testing environment
- [ ] Validate configuration edge cases and error scenarios
- [ ] Test cross-version compatibility (ServiceManager 3.x/4.x, PSR-11 v1/v2)
- [ ] Performance testing and optimization
- [ ] Security audit and dependency vulnerability checks

### Phase 4: Advanced Features & Integration (Week 4)
- [ ] Implement comprehensive logging integration
- [ ] Add event dispatching integration for monitoring
- [ ] Create console commands for schema management
- [ ] Add configuration caching and optimization
- [ ] Implement configuration migration utilities
- [ ] Add development tools and debugging helpers
- [ ] Create configuration validation CLI tools

### Phase 5: Documentation, Monitoring & Production Readiness (Week 5)
- [ ] Write detailed documentation and comprehensive examples
- [ ] Create migration guide from manual setup
- [ ] Implement health check endpoints for monitoring
- [ ] Add performance metrics collection and reporting
- [ ] Create monitoring dashboard examples
- [ ] Add alerting configuration examples
- [ ] Write troubleshooting guides and FAQ
- [ ] Create production deployment guides
- [ ] Final security review and penetration testing

## Modern PHP 8.3+ Features We'll Leverage

1. **Readonly Classes**: Immutable configuration objects
2. **Enums**: Type-safe adapter types and connection methods
3. **Named Arguments**: Clean factory method calls
4. **Union Types**: Flexible configuration input handling
5. **Attributes**: Configuration validation and dependency injection
6. **Match Expressions**: Clean factory logic

## Additional Considerations

### 1. Composer Configuration
The component should include comprehensive composer scripts and cross-version compatibility:

```json
{
    "require": {
        "php": "^8.3",
        "edgebinder/edgebinder": "^0.1.0",
        "edgebinder/weaviate-adapter": "^0.1.0",
        "laminas/laminas-servicemanager": "^3.0 || ^4.0",
        "psr/container": "^1.0 || ^2.0"
    },
    "scripts": {
        "cs-check": "phpcs src tests --standard=PSR12",
        "cs-fix": "phpcbf src tests --standard=PSR12",
        "phpstan": "phpstan analyse src tests --level=8",
        "security-audit": "composer audit --format=table",
        "test": "phpunit",
        "test-coverage": "phpunit --coverage-html coverage",
        "test-unit": "phpunit tests/Unit",
        "test-integration": "phpunit tests/Integration",
        "test-compatibility": "phpunit tests/Compatibility",
        "normalize": "@composer normalize",
        "normalize-check": "@composer normalize --dry-run"
    }
}
```

### 2. Cross-Version Compatibility Strategy

The component will support both Laminas ServiceManager versions and PSR-11 versions:

**ServiceManager 3.x vs 4.x Compatibility:**
- Use compatible factory interfaces that work with both versions
- Test against both ServiceManager versions in CI
- Handle any API differences in factory implementations

**PSR-11 v1 vs v2 Compatibility:**
- Use intersection types where beneficial but maintain v1 compatibility
- Ensure ContainerInterface usage works with both versions
- Test container behavior across versions

**Implementation Approach:**
```php
<?php
namespace EdgeBinder\Component\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

// This factory interface works with both ServiceManager 3.x and 4.x
class EdgeBinderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Implementation that works with both PSR-11 v1 and v2
        return $this->createEdgeBinder($container, 'default');
    }
}
```

### 3. Environment Variables for Testing
Support environment-based configuration for CI/CD:

```php
// In factory methods, support environment overrides
$weaviateClient = $config['weaviate_client'] ?? $_ENV['EDGEBINDER_WEAVIATE_CLIENT'] ?? 'weaviate.client.default';
$collectionName = $config['collection_name'] ?? $_ENV['EDGEBINDER_COLLECTION_NAME'] ?? 'EdgeBindings';
```

### 4. Integration Dependencies
The component requires these packages:
- ✅ `edgebinder/edgebinder` ^0.1.0 for the core EdgeBinder functionality with adapter registry (AVAILABLE)
- ✅ `edgebinder/weaviate-adapter` ^0.1.0 for Weaviate persistence (AVAILABLE)

Note: The Weaviate adapter handles its own client dependencies (such as `zestic/weaviate-client-component`) internally.

### 5. EdgeBinder Requirements ✅ AVAILABLE
This component requires EdgeBinder v1.0+ which includes:
- ✅ `EdgeBinder\Registry\AdapterFactoryInterface` - Interface for third-party adapters (IMPLEMENTED)
- ✅ `EdgeBinder\Registry\AdapterRegistry` - Static registry for adapter factories (IMPLEMENTED)
- ✅ `EdgeBinder\EdgeBinder::fromConfiguration()` - Factory method for configuration-based creation (IMPLEMENTED)
- ✅ Framework-agnostic extension points (IMPLEMENTED)

## Configuration Structure Comparison

### Nested Structure Benefits:
- **Clear Separation**: Instances vs adapters vs connections
- **Extensible**: Easy to add new adapter types
- **Organized**: Logical grouping of related configuration
- **Future-Proof**: Supports complex multi-adapter scenarios

### Flatter Structure Benefits:
- **Simpler**: Less nesting, easier to understand
- **Direct**: Instance configuration is immediate
- **Concise**: Less configuration overhead for simple cases
- **Familiar**: Similar to other Laminas component patterns

## Summary

This comprehensive plan provides:

- ✅ **Modern PHP 8.3+ architecture** with readonly classes and type safety
- ✅ **Multiple instance support** for RAG/analytics separation
- ✅ **Multiple Weaviate database support** for data isolation
- ✅ **Framework-agnostic extensible adapter system** via EdgeBinder registry (**IMPLEMENTED**)
- ✅ **Universal plugin architecture** that works across Laminas, Symfony, Laravel, Slim, etc. (**IMPLEMENTED**)
- ✅ **Cross-version compatibility** with ServiceManager 3.x/4.x and PSR-11 v1/v2
- ✅ **Comprehensive testing** with unit and integration tests
- ✅ **CI/CD workflows** matching EdgeBinder quality standards
- ✅ **Complete documentation** and usage examples
- ✅ **Flexible configuration** supporting both nested and flat structures (**IMPLEMENTED**)
- ✅ **Quality gates** with PHPStan level 8, PSR-12, and security audits
- ✅ **Health monitoring** capabilities for production environments

The component will provide a seamless, production-ready integration between EdgeBinder and Laminas/Mezzio applications, following modern PHP best practices and maintaining the same high quality standards as the core EdgeBinder library.

**Note**: The extensible adapter system described in this document has been **fully implemented** in EdgeBinder v1.0+, making this component plan even more viable and ready for implementation.
