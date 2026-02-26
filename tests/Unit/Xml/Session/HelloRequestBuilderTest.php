<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Session\HelloRequestBuilder;

final class HelloRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicHelloEnvelope(): void
    {
        $xml = (new HelloRequestBuilder())->build();

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">', $xml);
        self::assertStringContainsString('<hello/>', $xml);
        self::assertStringContainsString('</epp>', $xml);
    }
}
