<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactCreateRequest;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Xml\Contact\ContactCreateRequestBuilder;

#[Group('unit')]
final class ContactCreateRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicContactCreateXml(): void
    {
        $builder = new ContactCreateRequestBuilder();

        $xml = $builder->build(
            new ContactCreateRequest(
                'C-1',
                new ContactPostalInfo(
                    ContactPostalInfo::TYPE_LOC,
                    'Person Example',
                    'Example LLC',
                    new ContactAddress([ 'Main 1', 'Second 2' ], 'Belgrade', 'RS', 'BG', '11000'),
                ),
                '+381.111111',
                null,
                'person@example.rs',
                'pw<&>',
                1,
                new ContactExtension('123', 'ID', '2027-01-01', 'natId', '1', 'RS123'),
            ),
            'TRID<&>',
        );

        self::assertStringContainsString(
            '<contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>C-1</contact:id>', $xml);
        self::assertStringContainsString('<contact:street>Main 1</contact:street>', $xml);
        self::assertStringContainsString('<contact:pw>pw&lt;&amp;&gt;</contact:pw>', $xml);
        self::assertStringContainsString('<contact:disclose flag="1">', $xml);
        self::assertStringContainsString('<contactExt:ident>123</contactExt:ident>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
