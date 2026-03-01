<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Host\HostInfoResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class HostInfoResponseParserTest extends TestCase
{
    public function testParseMapsHostInfoData(): void
    {
        $parser = new HostInfoResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><resData>'
            . '<host:infData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
            . '<host:name>ns1.example.rs</host:name>'
            . '<host:roid>H-1</host:roid>'
            . '<host:status s="ok">Active</host:status>'
            . '<host:addr ip="v4">192.0.2.1</host:addr>'
            . '<host:addr ip="v6">2001:db8::1</host:addr>'
            . '<host:clID>ClientX</host:clID>'
            . '<host:crID>CreatorX</host:crID>'
            . '<host:upID>UpdaterX</host:upID>'
            . '<host:crDate>2026-01-01T00:00:00.0Z</host:crDate>'
            . '<host:upDate>2026-01-02T00:00:00.0Z</host:upDate>'
            . '<host:trDate>2026-01-03T00:00:00.0Z</host:trDate>'
            . '</host:infData>'
            . '</resData></response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertSame('ns1.example.rs', $response->name);
        self::assertSame('H-1', $response->roid);
        self::assertCount(1, $response->statuses);
        self::assertSame('ok', $response->statuses[0]);
        self::assertSame([ '192.0.2.1' ], $response->ipv4);
        self::assertSame([ '2001:db8::1' ], $response->ipv6);
        self::assertSame('ClientX', $response->clientId);
        self::assertInstanceOf(\DateTimeImmutable::class, $response->transferDate);
    }
}
