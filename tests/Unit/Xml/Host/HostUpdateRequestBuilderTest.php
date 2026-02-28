<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostUpdateRequest;
use RNIDS\Host\Dto\HostUpdateSection;
use RNIDS\Xml\Host\HostUpdateRequestBuilder;

#[Group('unit')]
final class HostUpdateRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicHostUpdateXml(): void
    {
        $builder = new HostUpdateRequestBuilder();

        $xml = $builder->build(
            new HostUpdateRequest(
                'ns1.example.rs',
                new HostUpdateSection([ new HostAddress('192.0.2.2', 'v4') ], [ 'ok' ]),
                new HostUpdateSection([ new HostAddress('2001:db8::2', 'v6') ], [ 'linked' ]),
                'ns2.example.rs',
            ),
            'TRID-2',
        );

        self::assertStringContainsString('<host:update xmlns:host="urn:ietf:params:xml:ns:host-1.0">', $xml);
        self::assertStringContainsString(
            '<host:add><host:addr ip="v4">192.0.2.2</host:addr><host:status s="ok"/></host:add>',
            $xml,
        );
        self::assertStringContainsString(
            '<host:rem><host:addr ip="v6">2001:db8::2</host:addr><host:status s="linked"/></host:rem>',
            $xml,
        );
        self::assertStringContainsString('<host:chg><host:name>ns2.example.rs</host:name></host:chg>', $xml);
    }
}
