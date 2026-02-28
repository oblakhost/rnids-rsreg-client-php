<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Xml\Domain\DomainCheckResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class DomainCheckResponseParserTest extends TestCase
{
    public function testParseMapsDomainCheckItemsWithAvailabilityAndReason(): void
    {
        $parser = new DomainCheckResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '<resData>'
            . '<domain:chkData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:cd>'
            . '<domain:name avail="1">example.rs</domain:name>'
            . '</domain:cd>'
            . '<domain:cd>'
            . '<domain:name avail="0">taken.rs</domain:name>'
            . '<domain:reason>In use</domain:reason>'
            . '</domain:cd>'
            . '</domain:chkData>'
            . '</resData>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertCount(2, $response->items);
        self::assertSame('example.rs', $response->items[0]->name);
        self::assertTrue($response->items[0]->available);
        self::assertNull($response->items[0]->reason);
        self::assertSame('taken.rs', $response->items[1]->name);
        self::assertFalse($response->items[1]->available);
        self::assertSame('In use', $response->items[1]->reason);
    }
}
