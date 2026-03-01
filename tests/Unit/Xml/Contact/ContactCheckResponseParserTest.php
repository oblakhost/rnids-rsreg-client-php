<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Contact\ContactCheckResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class ContactCheckResponseParserTest extends TestCase
{
    public function testParseMapsContactCheckItems(): void
    {
        $parser = new ContactCheckResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><resData>'
            . '<contact:chkData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
            . '<contact:cd><contact:id avail="1">C-1</contact:id></contact:cd>'
            . '<contact:cd><contact:id avail="0">C-2</contact:id><contact:reason>Taken</contact:reason></contact:cd>'
            . '</contact:chkData>'
            . '</resData></response>'
            . '</epp>',
            new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1'),
        );

        self::assertCount(2, $response->items);
        self::assertSame('C-1', $response->items[0]->id);
        self::assertTrue($response->items[0]->available);
        self::assertNull($response->items[0]->reason);
        self::assertSame('C-2', $response->items[1]->id);
        self::assertFalse($response->items[1]->available);
        self::assertSame('Taken', $response->items[1]->reason);
    }
}
