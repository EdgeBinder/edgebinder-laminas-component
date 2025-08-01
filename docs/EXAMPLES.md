# Usage Examples

This document provides practical examples of using the EdgeBinder Laminas Component in real-world scenarios.

## Basic Usage

### Simple Service with EdgeBinder

```php
<?php
namespace App\Service;

use EdgeBinder\EdgeBinder;

class DocumentService
{
    public function __construct(private EdgeBinder $edgeBinder) {}

    public function linkDocumentToCategory(object $document, object $category): void
    {
        $this->edgeBinder->bind(
            from: $document,
            to: $category,
            type: 'belongs_to',
            metadata: [
                'created_at' => new \DateTimeImmutable(),
                'confidence' => 0.95,
            ]
        );
    }

    public function findRelatedDocuments(object $document): array
    {
        return $this->edgeBinder->query()
            ->from($document)
            ->type('related_to')
            ->get();
    }
}
```

### Service Factory

```php
<?php
namespace App\Service\Factory;

use App\Service\DocumentService;
use EdgeBinder\EdgeBinder;
use Psr\Container\ContainerInterface;

class DocumentServiceFactory
{
    public function __invoke(ContainerInterface $container): DocumentService
    {
        return new DocumentService(
            $container->get(EdgeBinder::class)
        );
    }
}
```

## Multiple Instance Usage

### RAG (Retrieval-Augmented Generation) Service

```php
<?php
namespace App\Service;

use EdgeBinder\EdgeBinder;

class RAGService
{
    public function __construct(
        private EdgeBinder $ragBinder,
        private EdgeBinder $analyticsBinder
    ) {}

    public function indexDocument(object $document, object $knowledgeBase): void
    {
        // Create semantic relationship in RAG database
        $this->ragBinder->bind(
            from: $document,
            to: $knowledgeBase,
            type: 'semantic_similarity',
            metadata: [
                'embedding_model' => 'text-embedding-ada-002',
                'similarity_score' => 0.92,
                'vector_distance' => 0.08,
                'topics' => ['AI', 'machine-learning', 'NLP'],
            ]
        );

        // Track indexing event in analytics database
        $this->analyticsBinder->bind(
            from: $document,
            to: $knowledgeBase,
            type: 'indexed',
            metadata: [
                'timestamp' => new \DateTimeImmutable(),
                'processing_time_ms' => 150,
                'status' => 'success',
            ]
        );
    }

    public function findSimilarDocuments(object $document, float $threshold = 0.8): array
    {
        return $this->ragBinder->query()
            ->from($document)
            ->type('semantic_similarity')
            ->where('similarity_score', '>', $threshold)
            ->orderBy('similarity_score', 'desc')
            ->limit(10)
            ->get();
    }
}
```

### RAG Service Factory

```php
<?php
namespace App\Service\Factory;

use App\Service\RAGService;
use Psr\Container\ContainerInterface;

class RAGServiceFactory
{
    public function __invoke(ContainerInterface $container): RAGService
    {
        return new RAGService(
            $container->get('edgebinder.rag'),
            $container->get('edgebinder.analytics')
        );
    }
}
```

## Controller Integration

### Mezzio Request Handler

```php
<?php
namespace App\Handler;

use App\Service\RAGService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DocumentSearchHandler implements RequestHandlerInterface
{
    public function __construct(private RAGService $ragService) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $documentId = $queryParams['document_id'] ?? null;
        $threshold = (float) ($queryParams['threshold'] ?? 0.8);

        if (!$documentId) {
            return new JsonResponse(['error' => 'document_id is required'], 400);
        }

        // In a real application, you'd fetch the document entity
        $document = $this->getDocumentById($documentId);
        
        $similarDocuments = $this->ragService->findSimilarDocuments($document, $threshold);

        return new JsonResponse([
            'document_id' => $documentId,
            'threshold' => $threshold,
            'similar_documents' => array_map(
                fn($binding) => [
                    'id' => $binding->getToId(),
                    'type' => $binding->getToType(),
                    'similarity_score' => $binding->getMetadata()['similarity_score'] ?? null,
                    'topics' => $binding->getMetadata()['topics'] ?? [],
                ],
                $similarDocuments
            ),
        ]);
    }

    private function getDocumentById(string $id): object
    {
        // Implementation depends on your domain model
        return new class($id) {
            public function __construct(private string $id) {}
            public function getId(): string { return $this->id; }
        };
    }
}
```

### Handler Factory

```php
<?php
namespace App\Handler\Factory;

use App\Handler\DocumentSearchHandler;
use App\Service\RAGService;
use Psr\Container\ContainerInterface;

class DocumentSearchHandlerFactory
{
    public function __invoke(ContainerInterface $container): DocumentSearchHandler
    {
        return new DocumentSearchHandler(
            $container->get(RAGService::class)
        );
    }
}
```

## Analytics and Monitoring

### Analytics Service

```php
<?php
namespace App\Service;

use EdgeBinder\EdgeBinder;

class AnalyticsService
{
    public function __construct(private EdgeBinder $analyticsBinder) {}

    public function trackUserAction(object $user, object $resource, string $action): void
    {
        $this->analyticsBinder->bind(
            from: $user,
            to: $resource,
            type: $action,
            metadata: [
                'timestamp' => new \DateTimeImmutable(),
                'session_id' => session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]
        );
    }

    public function getUserActivity(object $user, \DateTimeInterface $since): array
    {
        return $this->analyticsBinder->query()
            ->from($user)
            ->where('timestamp', '>=', $since->format('c'))
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    public function getPopularResources(int $limit = 10): array
    {
        // This would require aggregation capabilities in your adapter
        return $this->analyticsBinder->query()
            ->type('viewed')
            ->groupBy('to_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

## Social Network Features

### Social Service

```php
<?php
namespace App\Service;

use EdgeBinder\EdgeBinder;

class SocialService
{
    public function __construct(private EdgeBinder $socialBinder) {}

    public function followUser(object $follower, object $followee): void
    {
        $this->socialBinder->bind(
            from: $follower,
            to: $followee,
            type: 'follows',
            metadata: [
                'followed_at' => new \DateTimeImmutable(),
                'relationship_strength' => 1.0,
            ]
        );
    }

    public function unfollowUser(object $follower, object $followee): void
    {
        $bindings = $this->socialBinder->query()
            ->from($follower)
            ->to($followee)
            ->type('follows')
            ->get();

        foreach ($bindings as $binding) {
            $this->socialBinder->unbind($binding->getId());
        }
    }

    public function getFollowers(object $user): array
    {
        return $this->socialBinder->query()
            ->to($user)
            ->type('follows')
            ->get();
    }

    public function getFollowing(object $user): array
    {
        return $this->socialBinder->query()
            ->from($user)
            ->type('follows')
            ->get();
    }

    public function getMutualConnections(object $user1, object $user2): array
    {
        $user1Following = $this->getFollowing($user1);
        $user2Following = $this->getFollowing($user2);

        // Find mutual connections (simplified example)
        $mutual = [];
        foreach ($user1Following as $binding1) {
            foreach ($user2Following as $binding2) {
                if ($binding1->getToId() === $binding2->getToId()) {
                    $mutual[] = $binding1;
                }
            }
        }

        return $mutual;
    }
}
```

## Configuration Examples

### Complete Application Configuration

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
                    'content' => ['dataType' => ['text']],
                    'embedding_model' => ['dataType' => ['string']],
                    'similarity_score' => ['dataType' => ['number']],
                    'topics' => ['dataType' => ['string[]']],
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
                    'action_type' => ['dataType' => ['string']],
                    'timestamp' => ['dataType' => ['date']],
                    'session_id' => ['dataType' => ['string']],
                    'ip_address' => ['dataType' => ['string']],
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
                    'relationship_type' => ['dataType' => ['string']],
                    'relationship_strength' => ['dataType' => ['number']],
                    'created_at' => ['dataType' => ['date']],
                ],
            ],
        ],
    ],
];
```

### Service Registration

```php
<?php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            App\Service\DocumentService::class => App\Service\Factory\DocumentServiceFactory::class,
            App\Service\RAGService::class => App\Service\Factory\RAGServiceFactory::class,
            App\Service\AnalyticsService::class => App\Service\Factory\AnalyticsServiceFactory::class,
            App\Service\SocialService::class => App\Service\Factory\SocialServiceFactory::class,
            App\Handler\DocumentSearchHandler::class => App\Handler\Factory\DocumentSearchHandlerFactory::class,
        ],
    ],
];
```

## Testing Examples

### Unit Test with Mocked EdgeBinder

```php
<?php
namespace App\Test\Service;

use App\Service\DocumentService;
use EdgeBinder\EdgeBinder;
use PHPUnit\Framework\TestCase;

class DocumentServiceTest extends TestCase
{
    public function testLinkDocumentToCategory(): void
    {
        $edgeBinder = $this->createMock(EdgeBinder::class);
        $service = new DocumentService($edgeBinder);

        $document = new class { public function getId(): string { return 'doc-1'; } };
        $category = new class { public function getId(): string { return 'cat-1'; } };

        $edgeBinder
            ->expects($this->once())
            ->method('bind')
            ->with(
                $document,
                $category,
                'belongs_to',
                $this->callback(function ($metadata) {
                    return isset($metadata['created_at']) && isset($metadata['confidence']);
                })
            );

        $service->linkDocumentToCategory($document, $category);
    }
}
```

This completes the basic examples. The component provides a solid foundation for integrating EdgeBinder into Laminas/Mezzio applications with support for multiple instances, proper dependency injection, and comprehensive configuration options.
