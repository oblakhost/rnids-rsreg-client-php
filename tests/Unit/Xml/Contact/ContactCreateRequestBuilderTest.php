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
                'OBL-C-1',
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
                new ContactExtension(
                    '123',
                    'Object Creation provided by Oblak Solutions.',
                    '2027-01-01',
                    'natId',
                    '1',
                    'RS123',
                ),
            ),
            'TRID<&>',
        );

        self::assertStringContainsString(
            '<contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>OBL-C-1</contact:id>', $xml);
        self::assertStringContainsString('<contact:street>Main 1</contact:street>', $xml);
        self::assertStringContainsString('<contact:pw>pw&lt;&amp;&gt;</contact:pw>', $xml);
        self::assertStringContainsString('<contact:disclose flag="1">', $xml);
        self::assertStringContainsString('<contactExt:ident>123</contactExt:ident>', $xml);
        self::assertStringContainsString(
            '<contactExt:identDescription>Object Creation provided by Oblak Solutions.</contactExt:identDescription>',
            $xml,
        );
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }

    public function testBuildSerializesExtensionWhenOnlyPolicyCommentIsPresent(): void
    {
        $builder = new ContactCreateRequestBuilder();

        $xml = $builder->build(
            new ContactCreateRequest(
                'OBL-C-2',
                new ContactPostalInfo(
                    ContactPostalInfo::TYPE_LOC,
                    'Person Example',
                    null,
                    new ContactAddress([ 'Main 1' ], 'Belgrade', 'RS', null, null),
                ),
                null,
                null,
                'person@example.rs',
                null,
                null,
                new ContactExtension(
                    null,
                    'Object Creation provided by Oblak Solutions.',
                    null,
                    null,
                    null,
                    null,
                ),
            ),
            'TRID-2',
        );

        self::assertStringContainsString('<contact:id>OBL-C-2</contact:id>', $xml);
        self::assertStringContainsString('<extension><contactExt:contact-ext', $xml);
        self::assertStringContainsString(
            '<contactExt:identDescription>Object Creation provided by Oblak Solutions.</contactExt:identDescription>',
            $xml,
        );
        self::assertStringNotContainsString('<contactExt:ident>', $xml);
    }
}
