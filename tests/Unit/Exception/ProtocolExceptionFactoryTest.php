<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Exception\AuthenticationFailure;
use RNIDS\Exception\ObjectMissing;
use RNIDS\Exception\ProtocolException;
use RNIDS\Exception\ProtocolExceptionFactory;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Unit tests for mapping response codes to protocol exceptions.
 */
#[Group('unit')]
final class ProtocolExceptionFactoryTest extends TestCase
{
    /**
     * Verifies authentication code maps to AuthenticationFailure.
     */
    public function testFactoryReturnsMappedExceptionForAuthenticationError(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2200));

        self::assertInstanceOf(AuthenticationFailure::class, $exception);
    }

    /**
     * Verifies object missing code maps to ObjectMissing.
     */
    public function testFactoryReturnsMappedExceptionForObjectDoesNotExist(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2303));

        self::assertInstanceOf(ObjectMissing::class, $exception);
    }

    /**
     * Verifies unmapped codes fall back to generic ProtocolException.
     */
    public function testFactoryReturnsGenericProtocolExceptionForUnknownCode(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2999));

        self::assertInstanceOf(\RNIDS\Exception\ProtocolException::class, $exception);
        self::assertSame(2999, $exception->resultCode());
    }

    /**
     * Builds response metadata fixture with the provided result code.
     */
    private function metadata(int $resultCode): ResponseMetadata
    {
        return new ResponseMetadata($resultCode, 'message', 'CL-1', 'SV-1');
    }
}
