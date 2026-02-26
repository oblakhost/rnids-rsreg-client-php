<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Xml\Domain\DomainCheckRequestBuilder;

final class DomainCheckRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainCheckXml(): void
    {
        $builder = new DomainCheckRequestBuilder();

        $xml = $builder->build(
            new DomainCheckRequest([ 'example.rs', 'escape<&>.rs' ]),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<check>', $xml);
        self::assertStringContainsString(
            '<domain:check xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example.rs</domain:name>', $xml);
        self::assertStringContainsString('<domain:name>escape&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
