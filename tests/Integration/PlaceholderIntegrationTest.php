<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Sanity test proving the integration test suite bootstrap works.
 */
final class PlaceholderIntegrationTest extends TestCase
{
    /**
     * Verifies that integration tests are executed by PHPUnit.
     */
    public function testIntegrationSuiteIsConfigured(): void
    {
        self::assertTrue(true);
    }
}
