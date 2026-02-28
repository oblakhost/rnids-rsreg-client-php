<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Domain\Dto\DomainTransferRequest;
use RNIDS\Xml\Domain\DomainTransferRequestBuilder;

#[Group('unit')]
final class DomainTransferRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainTransferXml(): void
    {
        $builder = new DomainTransferRequestBuilder();

        $xml = $builder->build(
            new DomainTransferRequest('request', 'example<&>.rs', 1, 'y', 'pw<&>'),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<transfer op="request">', $xml);
        self::assertStringContainsString(
            '<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString('<domain:period unit="y">1</domain:period>', $xml);
        self::assertStringContainsString('<domain:pw>pw&lt;&amp;&gt;</domain:pw>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
