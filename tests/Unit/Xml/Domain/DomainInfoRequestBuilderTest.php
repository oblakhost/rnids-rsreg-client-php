<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Xml\Domain\DomainInfoRequestBuilder;

#[Group('unit')]
final class DomainInfoRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainInfoXml(): void
    {
        $builder = new DomainInfoRequestBuilder();

        $xml = $builder->build(
            new DomainInfoRequest('example<&>.rs', DomainInfoRequest::HOSTS_DELEGATED),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<info>', $xml);
        self::assertStringContainsString(
            '<domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString(
            '<domain:name hosts="del">example&lt;&amp;&gt;.rs</domain:name>',
            $xml,
        );
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }

    public function testBuildOmitsHostsAttributeWhenUsingDefaultAllMode(): void
    {
        $builder = new DomainInfoRequestBuilder();

        $xml = $builder->build(
            new DomainInfoRequest('komodarstvo.rs', DomainInfoRequest::HOSTS_ALL),
            'TRID-DEFAULT',
        );

        self::assertStringContainsString('<domain:name>komodarstvo.rs</domain:name>', $xml);
        self::assertStringNotContainsString('<domain:name hosts="all">', $xml);
    }
}
