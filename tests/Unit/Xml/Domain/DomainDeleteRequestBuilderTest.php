<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto\DomainDeleteRequest;
use RNIDS\Xml\Domain\DomainDeleteRequestBuilder;

final class DomainDeleteRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainDeleteXml(): void
    {
        $builder = new DomainDeleteRequestBuilder();

        $xml = $builder->build(new DomainDeleteRequest('example<&>.rs'), 'TRID<&>');

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString(
            '<domain:delete xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
