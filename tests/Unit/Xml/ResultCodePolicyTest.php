<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use PHPUnit\Framework\TestCase;
use RNIDS\Exception\AuthenticationFailure;
use RNIDS\Exception\ProtocolException;
use RNIDS\Xml\Response\ResponseMetadata;
use RNIDS\Xml\ResultCodePolicy;

final class ResultCodePolicyTest extends TestCase
{
    public function testAssertSuccessDoesNotThrowForKnownSuccessCode(): void
    {
        ResultCodePolicy::assertSuccess($this->metadata(1000));

        self::assertTrue(true);
    }

    public function testAssertSuccessThrowsMappedExceptionForKnownFailureCode(): void
    {
        $this->expectException(AuthenticationFailure::class);

        ResultCodePolicy::assertSuccess($this->metadata(2200));
    }

    public function testAssertSuccessThrowsGenericProtocolExceptionForUnknownFailureCode(): void
    {
        try {
            ResultCodePolicy::assertSuccess($this->metadata(2999));
            self::fail('Expected protocol exception to be thrown.');
        } catch (\RNIDS\Exception\ProtocolException $exception) {
            self::assertSame(2999, $exception->resultCode());
        }
    }

    private function metadata(int $resultCode): ResponseMetadata
    {
        return new ResponseMetadata($resultCode, 'message', 'CL-1', 'SV-1');
    }
}
