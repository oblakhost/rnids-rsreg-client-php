<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Xml\Domain\DomainTransferResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class DomainTransferResponseParserTest extends TestCase
{
    public function testParseMapsDomainTransferData(): void
    {
        $parser = new DomainTransferResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '<resData>'
            . '<domain:trnData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name>'
            . '<domain:trStatus>pending</domain:trStatus>'
            . '<domain:reID>REQ-1</domain:reID>'
            . '<domain:reDate>2026-03-01T10:00:00.0Z</domain:reDate>'
            . '<domain:acID>ACT-1</domain:acID>'
            . '<domain:acDate>2026-03-06T10:00:00.0Z</domain:acDate>'
            . '<domain:exDate>2027-02-01T00:00:00.0Z</domain:exDate>'
            . '</domain:trnData>'
            . '</resData>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertSame('example.rs', $response->name);
        self::assertSame('pending', $response->transferStatus);
        self::assertSame('REQ-1', $response->requestClientId);
        self::assertSame('2026-03-01T10:00:00.0Z', $response->requestDate);
        self::assertSame('ACT-1', $response->actionClientId);
        self::assertSame('2026-03-06T10:00:00.0Z', $response->actionDate);
        self::assertSame('2027-02-01T00:00:00.0Z', $response->expirationDate);
    }
}
