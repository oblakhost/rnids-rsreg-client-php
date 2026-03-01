<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Contact\ContactInfoResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class ContactInfoResponseParserTest extends TestCase
{
    public function testParseMapsContactInfoIncludingRnidsExtension(): void
    {
        $parser = new ContactInfoResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<resData>'
            . '<contact:infData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
            . '<contact:id>C-1</contact:id>'
            . '<contact:roid>ROID-1</contact:roid>'
            . '<contact:status s="ok">Active</contact:status>'
            . '<contact:postalInfo type="loc">'
            . '<contact:name>Person Example</contact:name>'
            . '<contact:org>Example LLC</contact:org>'
            . '<contact:addr>'
            . '<contact:street>Main 1</contact:street>'
            . '<contact:street>Second 2</contact:street>'
            . '<contact:city>Belgrade</contact:city>'
            . '<contact:sp>BG</contact:sp>'
            . '<contact:pc>11000</contact:pc>'
            . '<contact:cc>RS</contact:cc>'
            . '</contact:addr>'
            . '</contact:postalInfo>'
            . '<contact:voice>+381.111111</contact:voice>'
            . '<contact:fax>+381.222222</contact:fax>'
            . '<contact:email>person@example.rs</contact:email>'
            . '<contact:clID>ClientX</contact:clID>'
            . '<contact:crID>CreatorX</contact:crID>'
            . '<contact:upID>UpdaterX</contact:upID>'
            . '<contact:crDate>2026-03-01T00:00:00.0Z</contact:crDate>'
            . '<contact:upDate>2026-03-02T00:00:00.0Z</contact:upDate>'
            . '<contact:trDate>2026-03-03T00:00:00.0Z</contact:trDate>'
            . '<contact:disclose flag="1"/>'
            . '</contact:infData>'
            . '</resData>'
            . '<extension>'
            . '<contactExt:contact-ext xmlns:contactExt="http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0">'
            . '<contactExt:ident>123</contactExt:ident>'
            . '<contactExt:identDescription>ID card</contactExt:identDescription>'
            . '<contactExt:identExpiry>2030-01-01</contactExt:identExpiry>'
            . '<contactExt:identKind>natId</contactExt:identKind>'
            . '<contactExt:isLegalEntity>1</contactExt:isLegalEntity>'
            . '<contactExt:vatNo>RS123</contactExt:vatNo>'
            . '</contactExt:contact-ext>'
            . '</extension>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1'),
        );

        self::assertSame('C-1', $response->id);
        self::assertSame('ROID-1', $response->roid);
        self::assertSame('ok', $response->statuses[0]);
        self::assertSame('Person Example', $response->postalInfo?->name);
        self::assertSame('Belgrade', $response->postalInfo?->address->city);
        self::assertSame([ 'Main 1', 'Second 2' ], $response->postalInfo?->address->streets);
        self::assertSame(1, $response->disclose);
        self::assertSame('123', $response->ident);
        self::assertSame('RS123', $response->vatNo);
    }
}
