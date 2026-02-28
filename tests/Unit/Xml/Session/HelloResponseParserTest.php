<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Response\ResponseMetadata;
use RNIDS\Xml\Session\HelloResponseParser;

#[Group('unit')]
final class HelloResponseParserTest extends TestCase
{
    public function testParseMapsGreetingFieldsAndServiceUris(): void
    {
        $parser = new HelloResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<greeting>'
            . '<svID>RNIDS EPP</svID>'
            . '<svDate>2026-02-27T00:00:00.0Z</svDate>'
            . '<svcMenu>'
            . '<version>1.0</version>'
            . '<lang>en</lang>'
            . '<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>'
            . '<svcExtension>'
            . '<extURI>http://www.rnids.rs/epp/xml/rnids-1.0</extURI>'
            . '</svcExtension>'
            . '</svcMenu>'
            . '</greeting>'
            . '</epp>',
            new ResponseMetadata(1000, 'Greeting', null, null),
        );

        self::assertSame('RNIDS EPP', $response->serverId);
        self::assertSame('2026-02-27T00:00:00.0Z', $response->serverDate);
        self::assertSame([ '1.0' ], $response->versions);
        self::assertSame([ 'en' ], $response->languages);
        self::assertSame([ 'urn:ietf:params:xml:ns:domain-1.0' ], $response->objectUris);
        self::assertSame([ 'http://www.rnids.rs/epp/xml/rnids-1.0' ], $response->extensionUris);
    }
}
