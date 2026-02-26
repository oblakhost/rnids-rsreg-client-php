<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Response;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Response\EppResultCode;

/**
 * Unit tests for EPP result code enum conversion.
 */
final class EppResultCodeTest extends TestCase
{
    /**
     * Verifies known numeric result codes resolve to enum cases.
     */
    public function testKnownSuccessCodeCanBeResolved(): void
    {
        self::assertSame(
            EppResultCode::CommandCompletedSuccessfully,
            EppResultCode::tryFrom(1000),
        );
    }

    /**
     * Verifies unknown numeric result codes produce null.
     */
    public function testUnknownCodeReturnsNull(): void
    {
        self::assertNull(EppResultCode::tryFrom(2999));
    }
}
