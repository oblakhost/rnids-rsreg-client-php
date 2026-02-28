<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostCreateRequest;
use RNIDS\Xml\Host\HostCreateRequestBuilder;

#[Group('unit')]
final class HostCreateRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicHostCreateXml(): void
    {
        $builder = new HostCreateRequestBuilder();

        $xml = $builder->build(
            new HostCreateRequest(
                'ns1.example.rs',
                [
                    new HostAddress('192.0.2.1', 'v4'),
                    new HostAddress('2001:db8::1', 'v6'),
                ],
            ),
            'TRID-1',
        );

        self::assertStringContainsString('<host:create xmlns:host="urn:ietf:params:xml:ns:host-1.0">', $xml);
        self::assertStringContainsString('<host:name>ns1.example.rs</host:name>', $xml);
        self::assertStringContainsString('<host:addr ip="v4">192.0.2.1</host:addr>', $xml);
        self::assertStringContainsString('<host:addr ip="v6">2001:db8::1</host:addr>', $xml);
    }
}
