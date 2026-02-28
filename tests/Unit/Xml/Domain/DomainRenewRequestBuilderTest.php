<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Domain\Dto\DomainRenewRequest;
use RNIDS\Xml\Domain\DomainRenewRequestBuilder;

#[Group('unit')]
final class DomainRenewRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainRenewXml(): void
    {
        $builder = new DomainRenewRequestBuilder();

        $xml = $builder->build(
            new DomainRenewRequest('example<&>.rs', '2027-02-01', 1, 'y'),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString(
            '<domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString('<domain:curExpDate>2027-02-01</domain:curExpDate>', $xml);
        self::assertStringContainsString('<domain:period unit="y">1</domain:period>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
