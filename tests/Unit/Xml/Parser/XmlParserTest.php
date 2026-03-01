<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Parser;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Parser\XmlParser;

#[Group('unit')]
final class XmlParserTest extends TestCase
{
    public function testCreateXPathReturnsRegisteredNamespacesForValidXml(): void
    {
        $xpath = XmlParser::createXPath('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '</response>'
            . '</epp>');

        self::assertSame(
            'Command completed successfully',
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:result/epp:msg'),
        );
    }

    public function testCreateXPathThrowsMalformedResponseExceptionWithDiagnosticsForMalformedXml(): void
    {
        $this->expectException(\RNIDS\Exception\MalformedResponseException::class);
        $this->expectExceptionMessage('EPP response contains malformed XML');
        $this->expectExceptionMessage('line');

        XmlParser::createXPath('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><result code="1000"><msg>ok</msg></result>'
            . '</epp>');
    }

    public function testCreateXPathClearsLibxmlErrorsBetweenCalls(): void
    {
        try {
            XmlParser::createXPath('<?xml version="1.0" encoding="UTF-8"?>'
                . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response>');
            self::fail('Expected malformed XML exception.');
        } catch (\RNIDS\Exception\MalformedResponseException) {
        }

        $xpath = XmlParser::createXPath('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Recovered</msg></result>'
            . '</response>'
            . '</epp>');

        self::assertSame(
            'Recovered',
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:result/epp:msg'),
        );
    }
}
