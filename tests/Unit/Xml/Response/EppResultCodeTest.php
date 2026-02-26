<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Response;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Response\EppResultCode;

final class EppResultCodeTest extends TestCase
{
    public function testKnownSuccessCodeCanBeResolved(): void
    {
        self::assertSame(
            EppResultCode::CommandCompletedSuccessfully,
            EppResultCode::tryFrom(1000),
        );
    }

    public function testUnknownCodeReturnsNull(): void
    {
        self::assertNull(EppResultCode::tryFrom(2999));
    }
}
