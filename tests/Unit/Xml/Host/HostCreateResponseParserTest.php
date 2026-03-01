<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Host\HostCreateResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class HostCreateResponseParserTest extends TestCase
{
    public function testParseMapsHostCreateData(): void
    {
        $parser = new HostCreateResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><resData>'
            . '<host:creData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
            . '<host:name>ns1.example.rs</host:name>'
            . '<host:crDate>2026-01-01T00:00:00.0Z</host:crDate>'
            . '</host:creData>'
            . '</resData></response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertSame('ns1.example.rs', $response->name);
        self::assertInstanceOf(\DateTimeImmutable::class, $response->createDate);
        self::assertSame('2026-01-01T00:00:00+00:00', $response->createDate?->format('c'));
    }
}
