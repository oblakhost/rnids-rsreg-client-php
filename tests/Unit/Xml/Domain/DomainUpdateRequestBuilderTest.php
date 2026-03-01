<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto\DomainExtension;
use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainUpdateRequest;
use RNIDS\Domain\Dto\DomainUpdateSection;
use RNIDS\Xml\Domain\DomainUpdateRequestBuilder;

#[Group('unit')]
final class DomainUpdateRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicDomainUpdateXml(): void
    {
        $builder = new DomainUpdateRequestBuilder();

        $xml = $builder->build(
            new DomainUpdateRequest(
                'example<&>.rs',
                new DomainUpdateSection(
                    [ new DomainRegisterContact('admin', 'ADM-2') ],
                    [ 'clientUpdateProhibited' ],
                ),
                new DomainUpdateSection(
                    [ new DomainRegisterContact('admin', 'ADM-1') ],
                    [ 'ok' ],
                ),
                null,
                'new-auth',
            ),
            'TRID<&>',
        );

        self::assertStringContainsString(
            '<domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domain:name>example&lt;&amp;&gt;.rs</domain:name>', $xml);
        self::assertStringContainsString(
            '<domain:add><domain:contact type="admin">ADM-2</domain:contact>'
            . '<domain:status s="clientUpdateProhibited"/></domain:add>',
            $xml,
        );
        self::assertStringContainsString(
            '<domain:rem><domain:contact type="admin">ADM-1</domain:contact>'
            . '<domain:status s="ok"/></domain:rem>',
            $xml,
        );
        self::assertStringContainsString(
            '<domain:chg><domain:authInfo><domain:pw>new-auth</domain:pw></domain:authInfo></domain:chg>',
            $xml,
        );
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }

    public function testBuildSerializesRnidsDomainExtensionWhenProvided(): void
    {
        $builder = new DomainUpdateRequestBuilder();

        $xml = $builder->build(
            new DomainUpdateRequest(
                'example.rs',
                new DomainUpdateSection([ new DomainRegisterContact('admin', 'ADM-2') ], []),
                new DomainUpdateSection([ new DomainRegisterContact('admin', 'ADM-1') ], []),
                null,
                null,
                new DomainExtension('Remark', true, 'normal', false, true),
            ),
            'TRID-EXT-1',
        );

        self::assertStringContainsString(
            '<domainExt:domain-ext xmlns:domainExt="http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0">',
            $xml,
        );
        self::assertStringContainsString('<domainExt:remark>Remark</domainExt:remark>', $xml);
        self::assertStringContainsString('<domainExt:isWhoisPrivacy>true</domainExt:isWhoisPrivacy>', $xml);
        self::assertStringContainsString('<domainExt:operationMode>normal</domainExt:operationMode>', $xml);
        self::assertStringContainsString('<domainExt:notifyAdmin>false</domainExt:notifyAdmin>', $xml);
        self::assertStringContainsString('<domainExt:dnsSec>true</domainExt:dnsSec>', $xml);
    }
}
