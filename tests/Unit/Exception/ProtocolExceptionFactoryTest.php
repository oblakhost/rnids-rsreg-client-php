<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RNIDS\Exception\AuthenticationFailure;
use RNIDS\Exception\ObjectMissing;
use RNIDS\Exception\ProtocolException;
use RNIDS\Exception\ProtocolExceptionFactory;
use RNIDS\Xml\Response\ResponseMetadata;

final class ProtocolExceptionFactoryTest extends TestCase
{
    public function testFactoryReturnsMappedExceptionForAuthenticationError(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2200));

        self::assertInstanceOf(AuthenticationFailure::class, $exception);
    }

    public function testFactoryReturnsMappedExceptionForObjectDoesNotExist(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2303));

        self::assertInstanceOf(ObjectMissing::class, $exception);
    }

    public function testFactoryReturnsGenericProtocolExceptionForUnknownCode(): void
    {
        $exception = ProtocolExceptionFactory::fromMetadata($this->metadata(2999));

        self::assertInstanceOf(\RNIDS\Exception\ProtocolException::class, $exception);
        self::assertSame(2999, $exception->resultCode());
    }

    private function metadata(int $resultCode): ResponseMetadata
    {
        return new ResponseMetadata($resultCode, 'message', 'CL-1', 'SV-1');
    }
}
