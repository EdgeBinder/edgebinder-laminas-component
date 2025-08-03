# Configuration Reference

This document provides a comprehensive reference for configuring the EdgeBinder Laminas Component.

## Basic Configuration

### Single Instance Configuration

The simplest configuration uses a single EdgeBinder instance:

```php
<?php
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

### Configuration Keys

| Key | Type | Required | Default | Description |
|-----|------|----------|---------|-------------|
| `adapter` | string | Yes | None | Adapter type to use (`inmemory`, `weaviate`, `redis`, `janus`, etc.) |

**Adapter-Specific Configuration:**

**InMemoryAdapter** (no additional configuration needed):
```php
'adapter' => 'inmemory'
```

**WeaviateAdapter** (requires `edgebinder/weaviate-adapter`):
| Key | Type | Required | Default | Description |
|-----|------|----------|---------|-------------|
| `weaviate_client` | string | Yes | `'weaviate.client.default'` | Weaviate client service name |
| `collection_name` | string | No | `'EdgeBindings'` | Weaviate collection name |
| `schema` | array | No | `['auto_create' => true]` | Collection schema configuration |

## Multiple Instance Configuration

Configure multiple EdgeBinder instances for different use cases:

```php
<?php
// config/autoload/edgebinder.local.php
return [
    'edgebinder' => [
        // Testing instance
        'test' => [
            'adapter' => 'inmemory',
        ],

        // RAG system (requires edgebinder/weaviate-adapter)
        'rag' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.rag',
            'collection_name' => 'RAGBindings',
            'schema' => [
                'auto_create' => true,
                'vectorizer' => 'text2vec-openai',
            ],
        ],
        // Analytics system (requires edgebinder/janus-adapter)
        'analytics' => [
            'adapter' => 'janus',
            'janus_client' => 'janus.client.analytics',
            'graph_name' => 'AnalyticsGraph',
            'consistency_level' => 'eventual',
        ],

        // Cache system (requires edgebinder/redis-adapter)
        'cache' => [
            'adapter' => 'redis',
            'redis_client' => 'redis.client.cache',
            'ttl' => 3600,
            'prefix' => 'edgebinder:',
        ],
    ],
];
```

## Weaviate Schema Configuration

### Auto-Create Schema

```php
'schema' => [
    'auto_create' => true,
    'vectorizer' => 'text2vec-openai',
]
```

### Custom Schema

```php
'schema' => [
    'auto_create' => true,
    'vectorizer' => 'text2vec-openai',
    'properties' => [
        'title' => [
            'dataType' => ['string'],
            'description' => 'Binding title',
            'tokenization' => 'word',
        ],
        'content' => [
            'dataType' => ['text'],
            'description' => 'Binding content',
            'tokenization' => 'word',
        ],
        'metadata' => [
            'dataType' => ['object'],
            'description' => 'Additional metadata',
        ],
        'created_at' => [
            'dataType' => ['date'],
            'description' => 'Creation timestamp',
        ],
        'score' => [
            'dataType' => ['number'],
            'description' => 'Relevance score',
        ],
    ],
    'vectorIndexConfig' => [
        'distance' => 'cosine',
        'efConstruction' => 128,
        'maxConnections' => 64,
    ],
]
```

## Service Names

### EdgeBinder Services

| Service Name | Description |
|--------------|-------------|
| `EdgeBinder::class` | Main EdgeBinder service (default instance) |
| `'EdgeBinder'` | Alias for main EdgeBinder service |
| `'edgebinder'` | Alias for default instance |
| `'edgebinder.default'` | Default EdgeBinder instance |

**Named Instance Services** (when configured):
| Service Name | Description |
|--------------|-------------|
| `'edgebinder.test'` | Testing EdgeBinder instance |
| `'edgebinder.rag'` | RAG-specific EdgeBinder instance |
| `'edgebinder.analytics'` | Analytics EdgeBinder instance |
| `'edgebinder.cache'` | Cache EdgeBinder instance |

**Note**: Adapter services are managed by their respective packages and register themselves with the EdgeBinder AdapterRegistry.

## Environment-Specific Configuration

### Development Configuration

```php
<?php
// config/autoload/edgebinder.development.local.php
return [
    'edgebinder' => [
        // Use InMemoryAdapter for fast development
        'adapter' => 'inmemory',

        // Or use external adapter for integration testing
        // 'adapter' => 'weaviate',
        // 'weaviate_client' => 'weaviate.client.development',
        // 'collection_name' => 'EdgeBindings_Dev',
        // 'schema' => [
        //     'auto_create' => true,
        //     'vectorizer' => 'text2vec-openai',
        // ],
    ],
];
```

### Production Configuration

```php
<?php
// config/autoload/edgebinder.production.local.php
return [
    'edgebinder' => [
        'adapter' => 'weaviate', // or 'redis', 'janus', etc.
        'weaviate_client' => 'weaviate.client.production',
        'collection_name' => 'EdgeBindings',
        'schema' => [
            'auto_create' => false, // Pre-create schema in production
            'vectorizer' => 'text2vec-openai',
        ],
    ],
];
```

## Custom Adapter Configuration

### Custom Adapter Example

EdgeBinder uses a self-determining adapter architecture. Adapters register themselves when their packages are loaded.

**Option 1: Install existing adapter packages**
```bash
composer require edgebinder/redis-adapter
composer require edgebinder/janus-adapter
```

**Option 2: Create custom adapter**
```php
<?php
// In your bootstrap or ConfigProvider
use EdgeBinder\Registry\AdapterRegistry;
use MyVendor\CustomAdapter\CustomAdapterFactory;

// Register the custom adapter
AdapterRegistry::register(new CustomAdapterFactory());

// Then configure it
return [
    'edgebinder' => [
        'custom_instance' => [
            'adapter' => 'custom',
            'custom_client' => 'custom.client.service',
            'custom_config' => [
                'setting1' => 'value1',
                'setting2' => 'value2',
            ],
        ],
    ],
];
```

## Configuration Validation

The component validates configuration and provides helpful error messages:

### Missing Configuration

```
EdgeBinder configuration is missing: config service not found in container
```

### Invalid Configuration

```
EdgeBinder configuration is invalid: config service must return an array
```

### Missing Adapter Configuration

```
Adapter configuration is missing. Please specify an 'adapter' in your EdgeBinder configuration.
```

### Instance Not Configured

```
EdgeBinder instance "rag" is not configured. Please add configuration for this instance.
```

### Unsupported Adapter

```
Unsupported adapter type "custom". Please install the adapter package or register the adapter factory.
```

## Best Practices

### 1. Use Environment-Specific Files

- `edgebinder.global.php` - Global defaults
- `edgebinder.local.php` - Environment-specific overrides
- `edgebinder.development.local.php` - Development settings
- `edgebinder.production.local.php` - Production settings

### 2. Start with InMemoryAdapter for Development

```php
'edgebinder' => [
    'adapter' => 'inmemory', // Fast, no external dependencies
]
```

### 3. Separate Concerns with Multiple Instances

```php
'edgebinder' => [
    'test' => ['adapter' => 'inmemory'],
    'rag' => ['adapter' => 'weaviate', /* RAG-specific config */],
    'analytics' => ['adapter' => 'janus', /* Analytics-specific config */],
    'cache' => ['adapter' => 'redis', /* Cache-specific config */],
]
```

### 4. Use Descriptive Collection/Database Names

```php
'collection_name' => 'RAGBindings_v1_Production'  // For Weaviate
'graph_name' => 'AnalyticsGraph_Production'       // For Janus
'prefix' => 'edgebinder:prod:'                    // For Redis
```

### 5. Pre-create Schemas in Production (for applicable adapters)

```php
'schema' => [
    'auto_create' => false, // Set to false in production for Weaviate
]
```

### 6. Configure Appropriate Vectorizers (for Weaviate)

```php
'schema' => [
    'vectorizer' => 'text2vec-openai', // For OpenAI embeddings
    // or
    'vectorizer' => 'text2vec-huggingface', // For Hugging Face models
    // or
    'vectorizer' => 'none', // For custom vectors
]
```

## Troubleshooting

### Common Configuration Issues

1. **Missing adapter configuration**: Ensure you specify an `adapter` in your configuration
2. **Adapter package not installed**: Install the required adapter package (e.g., `edgebinder/weaviate-adapter`)
3. **Service not found**: Ensure all referenced services are registered (for external adapters)
4. **Invalid schema**: Validate adapter-specific schema syntax (e.g., Weaviate schema)
5. **Connection issues**: Check external service configuration (e.g., Weaviate client, Redis client)
6. **Permission errors**: Verify external service API permissions

### Quick Debug Steps

1. **Start with InMemoryAdapter** to verify basic functionality:
   ```php
   'adapter' => 'inmemory'
   ```

2. **Check adapter registration**:
   ```php
   // In your bootstrap
   $registeredTypes = \EdgeBinder\Registry\AdapterRegistry::getRegisteredTypes();
   var_dump($registeredTypes); // Should include your adapter type
   ```

3. **Verify service registration** (for external adapters):
   ```php
   // Check if required services exist
   $container->has('weaviate.client.default'); // For Weaviate
   $container->has('redis.client.cache');      // For Redis
   ```
