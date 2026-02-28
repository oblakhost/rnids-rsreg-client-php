<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRegisterExtension;
use RNIDS\Domain\Dto\DomainRegisterNameserver;
use RNIDS\Domain\Dto\DomainRegisterRequest;
use RNIDS\Xml\Domain\DomainRegisterRequestBuilder;

#[Group('unit')]
final class DomainRegisterRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainRegisterXml(): void
    {
        $builder = new DomainRegisterRequestBuilder();

        $xml = $builder->build(
            new DomainRegisterRequest(
                name: 'example<&>.rs',
                period: 1,
                periodUnit: 'y',
                nameservers: [
                    new DomainRegisterNameserver('ns1.example.rs'),
                    new DomainRegisterNameserver('ns2.example.rs', [ '192.0.2.2', '2001:db8::2' ]),
                ],
                registrant: 'REG-1',
                contacts: [
                    new DomainRegisterContact('admin', 'ADM-1'),
                    new DomainRegisterContact('tech', 'TEC-1'),
                ],
                authInfo: 'pw<&>',
                extension: new DomainRegisterExtension('Note<&>', true, 'secure', false, true),
            ),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString(
            '<domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString('<domain:period unit="y">1</domain:period>', $xml);
        self::assertStringContainsString('<domain:hostObj>ns1.example.rs</domain:hostObj>', $xml);
        self::assertStringContainsString('<domain:hostName>ns2.example.rs</domain:hostName>', $xml);
        self::assertStringContainsString('<domain:hostAddr>192.0.2.2</domain:hostAddr>', $xml);
        self::assertStringContainsString('<domain:hostAddr>2001:db8::2</domain:hostAddr>', $xml);
        self::assertStringContainsString('<domain:registrant>REG-1</domain:registrant>', $xml);
        self::assertStringContainsString('<domain:contact type="admin">ADM-1</domain:contact>', $xml);
        self::assertStringContainsString('<domain:contact type="tech">TEC-1</domain:contact>', $xml);
        self::assertStringContainsString('<domain:pw>pw&lt;&amp;&gt;</domain:pw>', $xml);
        self::assertStringContainsString('<domainExt:remark>Note&lt;&amp;&gt;</domainExt:remark>', $xml);
        self::assertStringContainsString('<domainExt:isWhoisPrivacy>true</domainExt:isWhoisPrivacy>', $xml);
        self::assertStringContainsString('<domainExt:operationMode>secure</domainExt:operationMode>', $xml);
        self::assertStringContainsString('<domainExt:notifyAdmin>false</domainExt:notifyAdmin>', $xml);
        self::assertStringContainsString('<domainExt:dnsSec>true</domainExt:dnsSec>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
