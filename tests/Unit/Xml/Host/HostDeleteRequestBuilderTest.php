<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\Dto\HostDeleteRequest;
use RNIDS\Xml\Host\HostDeleteRequestBuilder;

#[Group('unit')]
final class HostDeleteRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicHostDeleteXml(): void
    {
        $builder = new HostDeleteRequestBuilder();

        $xml = $builder->build(new HostDeleteRequest('ns<&>.example.rs'), 'TRID<&>');

        self::assertStringContainsString('<host:delete xmlns:host="urn:ietf:params:xml:ns:host-1.0">', $xml);
        self::assertStringContainsString('<host:name>ns&lt;&amp;&gt;.example.rs</host:name>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
