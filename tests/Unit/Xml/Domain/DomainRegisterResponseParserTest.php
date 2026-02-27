<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Domain\DomainRegisterResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class DomainRegisterResponseParserTest extends TestCase
{
    public function testParseMapsDomainCreateData(): void
    {
        $parser = new DomainRegisterResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '<resData>'
            . '<domain:creData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name>'
            . '<domain:crDate>2026-02-01T00:00:00.0Z</domain:crDate>'
            . '<domain:exDate>2027-02-01T00:00:00.0Z</domain:exDate>'
            . '</domain:creData>'
            . '</resData>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1'),
        );

        self::assertSame('example.rs', $response->name);
        self::assertSame('2026-02-01T00:00:00.0Z', $response->createDate);
        self::assertSame('2027-02-01T00:00:00.0Z', $response->expirationDate);
    }
}
