<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Domain\DomainInfoResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class DomainInfoResponseParserTest extends TestCase
{
    public function testParseMapsDomainInfoIncludingExtensionData(): void
    {
        $parser = new DomainInfoResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '<resData>'
            . '<domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name>'
            . '<domain:roid>D0001-RS</domain:roid>'
            . '<domain:status s="ok">Active</domain:status>'
            . '<domain:registrant>REG-1</domain:registrant>'
            . '<domain:contact type="admin">ADM-1</domain:contact>'
            . '<domain:contact type="tech">TEC-1</domain:contact>'
            . '<domain:ns>'
            . '<domain:hostObj>ns1.example.rs</domain:hostObj>'
            . '<domain:hostAttr>'
            . '<domain:hostName>ns2.example.rs</domain:hostName>'
            . '<domain:hostAddr>192.0.2.2</domain:hostAddr>'
            . '<domain:hostAddr>2001:db8::2</domain:hostAddr>'
            . '</domain:hostAttr>'
            . '</domain:ns>'
            . '<domain:clID>ClientX</domain:clID>'
            . '<domain:crID>CreatorX</domain:crID>'
            . '<domain:upID>UpdaterX</domain:upID>'
            . '<domain:crDate>2026-01-01T00:00:00.0Z</domain:crDate>'
            . '<domain:upDate>2026-01-02T00:00:00.0Z</domain:upDate>'
            . '<domain:exDate>2027-01-01T00:00:00.0Z</domain:exDate>'
            . '</domain:infData>'
            . '</resData>'
            . '<extension>'
            . '<domainExt:domain-ext xmlns:domainExt="http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0">'
            . '<domainExt:isWhoisPrivacy>1</domainExt:isWhoisPrivacy>'
            . '<domainExt:operationMode>secure</domainExt:operationMode>'
            . '<domainExt:notifyAdmin>0</domainExt:notifyAdmin>'
            . '<domainExt:dnsSec>1</domainExt:dnsSec>'
            . '<domainExt:remark>Domain note</domainExt:remark>'
            . '</domainExt:domain-ext>'
            . '</extension>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertSame('example.rs', $response->name);
        self::assertSame('D0001-RS', $response->roid);
        self::assertCount(1, $response->statuses);
        self::assertSame('ok', $response->statuses[0]->value);
        self::assertSame('Active', $response->statuses[0]->description);
        self::assertSame('REG-1', $response->registrant);
        self::assertCount(2, $response->contacts);
        self::assertSame('admin', $response->contacts[0]->type);
        self::assertSame('ADM-1', $response->contacts[0]->handle);
        self::assertCount(2, $response->nameservers);
        self::assertSame('ns1.example.rs', $response->nameservers[0]->name);
        self::assertSame([], $response->nameservers[0]->addresses);
        self::assertSame('ns2.example.rs', $response->nameservers[1]->name);
        self::assertSame([ '192.0.2.2', '2001:db8::2' ], $response->nameservers[1]->addresses);
        self::assertSame('1', $response->extension->isWhoisPrivacy);
        self::assertSame('secure', $response->extension->operationMode);
        self::assertSame('0', $response->extension->notifyAdmin);
        self::assertSame('1', $response->extension->dnsSec);
        self::assertSame('Domain note', $response->extension->remark);
    }
}
