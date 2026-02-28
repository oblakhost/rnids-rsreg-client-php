<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Host\HostCheckResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class HostCheckResponseParserTest extends TestCase
{
    public function testParseMapsHostCheckItems(): void
    {
        $parser = new HostCheckResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><resData>'
            . '<host:chkData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
            . '<host:cd><host:name avail="1">ns1.example.rs</host:name></host:cd>'
            . '<host:cd><host:name avail="0">ns2.example.rs</host:name><host:reason>In use</host:reason></host:cd>'
            . '</host:chkData>'
            . '</resData></response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertCount(2, $response->items);
        self::assertSame('ns1.example.rs', $response->items[0]->name);
        self::assertTrue($response->items[0]->available);
        self::assertSame('ns2.example.rs', $response->items[1]->name);
        self::assertFalse($response->items[1]->available);
        self::assertSame('In use', $response->items[1]->reason);
    }
}
