<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Contact\ContactCreateResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class ContactCreateResponseParserTest extends TestCase
{
    public function testParseMapsContactCreateResponse(): void
    {
        $parser = new ContactCreateResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><resData>'
            . '<contact:creData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
            . '<contact:id>C-10</contact:id>'
            . '<contact:crDate>2026-03-01T00:00:00.0Z</contact:crDate>'
            . '</contact:creData>'
            . '</resData></response>'
            . '</epp>',
            new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1'),
        );

        self::assertSame('C-10', $response->id);
        self::assertSame('2026-03-01T00:00:00.0Z', $response->createDate);
    }
}
