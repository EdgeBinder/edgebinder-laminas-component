<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Placeholder integration test.
 *
 * This test exists to prevent PHPUnit from failing when the Integration test suite is empty.
 * Real integration tests that require external services like Weaviate should be added here.
 */
#[CoversNothing]
final class PlaceholderIntegrationTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->assertTrue(true, 'Integration test suite is ready for real tests');
    }
}
