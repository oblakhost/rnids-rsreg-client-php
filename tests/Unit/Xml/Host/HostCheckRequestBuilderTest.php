<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\Dto\HostCheckRequest;
use RNIDS\Xml\Host\HostCheckRequestBuilder;

#[Group('unit')]
final class HostCheckRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicHostCheckXml(): void
    {
        $builder = new HostCheckRequestBuilder();

        $xml = $builder->build(new HostCheckRequest([ 'ns1.example.rs', 'ns<&>.example.rs' ]), 'TRID<&>');

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<host:check xmlns:host="urn:ietf:params:xml:ns:host-1.0">', $xml);
        self::assertStringContainsString('<host:name>ns1.example.rs</host:name>', $xml);
        self::assertStringContainsString('<host:name>ns&lt;&amp;&gt;.example.rs</host:name>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
