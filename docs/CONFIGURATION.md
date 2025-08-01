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

### Configuration Keys

| Key | Type | Required | Default | Description |
|-----|------|----------|---------|-------------|
| `adapter` | string | Yes | `'weaviate'` | Adapter type to use |
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
        'rag' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.rag',
            'collection_name' => 'RAGBindings',
            'schema' => [
                'auto_create' => true,
                'vectorizer' => 'text2vec-openai',
                'properties' => [
                    'content' => [
                        'dataType' => ['text'],
                        'description' => 'Document content',
                    ],
                    'metadata' => [
                        'dataType' => ['object'],
                        'description' => 'Document metadata',
                    ],
                ],
            ],
        ],
        'analytics' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.analytics',
            'collection_name' => 'AnalyticsBindings',
            'schema' => [
                'auto_create' => true,
                'properties' => [
                    'event_type' => [
                        'dataType' => ['string'],
                        'description' => 'Type of analytics event',
                    ],
                    'timestamp' => [
                        'dataType' => ['date'],
                        'description' => 'Event timestamp',
                    ],
                ],
            ],
        ],
        'social' => [
            'adapter' => 'weaviate',
            'weaviate_client' => 'weaviate.client.social',
            'collection_name' => 'SocialBindings',
            'schema' => [
                'auto_create' => true,
                'properties' => [
                    'relationship_type' => [
                        'dataType' => ['string'],
                        'description' => 'Type of social relationship',
                    ],
                    'strength' => [
                        'dataType' => ['number'],
                        'description' => 'Relationship strength score',
                    ],
                ],
            ],
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
| `'edgebinder.rag'` | RAG-specific EdgeBinder instance |
| `'edgebinder.analytics'` | Analytics EdgeBinder instance |
| `'edgebinder.social'` | Social EdgeBinder instance |

### Adapter Services

| Service Name | Description |
|--------------|-------------|
| `WeaviateAdapterFactory::class` | Weaviate adapter factory |
| `'edgebinder.adapter.weaviate.default'` | Default Weaviate adapter |

## Environment-Specific Configuration

### Development Configuration

```php
<?php
// config/autoload/edgebinder.development.local.php
return [
    'edgebinder' => [
        'adapter' => 'weaviate',
        'weaviate_client' => 'weaviate.client.development',
        'collection_name' => 'EdgeBindings_Dev',
        'schema' => [
            'auto_create' => true,
            'vectorizer' => 'text2vec-openai',
        ],
    ],
];
```

### Production Configuration

```php
<?php
// config/autoload/edgebinder.production.local.php
return [
    'edgebinder' => [
        'adapter' => 'weaviate',
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

### Redis Adapter Example

```php
<?php
// First, register the custom adapter in your bootstrap
use EdgeBinder\Registry\AdapterRegistry;
use MyVendor\RedisAdapter\RedisAdapterFactory;

AdapterRegistry::register(new RedisAdapterFactory());

// Then configure it
return [
    'edgebinder' => [
        'cache' => [
            'adapter' => 'redis',
            'redis_client' => 'redis.client.cache',
            'ttl' => 3600,
            'prefix' => 'edgebinder:',
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

### Instance Not Configured

```
EdgeBinder instance "rag" is not configured. Please add configuration for this instance.
```

### Unsupported Adapter

```
Unsupported adapter type "redis". Please register the adapter factory or use a built-in adapter.
```

## Best Practices

### 1. Use Environment-Specific Files

- `edgebinder.global.php` - Global defaults
- `edgebinder.local.php` - Environment-specific overrides
- `edgebinder.development.local.php` - Development settings
- `edgebinder.production.local.php` - Production settings

### 2. Separate Concerns with Multiple Instances

```php
'edgebinder' => [
    'rag' => [/* RAG-specific config */],
    'analytics' => [/* Analytics-specific config */],
    'social' => [/* Social-specific config */],
]
```

### 3. Use Descriptive Collection Names

```php
'collection_name' => 'RAGBindings_v1_Production'
```

### 4. Pre-create Schemas in Production

```php
'schema' => [
    'auto_create' => false, // Set to false in production
]
```

### 5. Configure Appropriate Vectorizers

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

1. **Service not found**: Ensure all referenced services are registered
2. **Invalid schema**: Validate Weaviate schema syntax
3. **Connection issues**: Check Weaviate client configuration
4. **Permission errors**: Verify Weaviate API permissions

### Debug Configuration

Enable debug mode to see detailed configuration information:

```php
'edgebinder' => [
    'debug' => true, // Enable debug mode
    // ... other configuration
]
```
