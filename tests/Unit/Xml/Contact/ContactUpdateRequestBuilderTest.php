<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Contact\Dto\ContactUpdateRequest;
use RNIDS\Xml\Contact\ContactUpdateRequestBuilder;

#[Group('unit')]
final class ContactUpdateRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicContactUpdateXml(): void
    {
        $builder = new ContactUpdateRequestBuilder();

        $xml = $builder->build(
            new ContactUpdateRequest(
                'OBL-C-1',
                [ 'ok' ],
                [ 'linked' ],
                new ContactPostalInfo(
                    ContactPostalInfo::TYPE_LOC,
                    'Updated Person',
                    null,
                    new ContactAddress([ 'Main 10' ], 'Belgrade', 'RS', null, null),
                ),
                '+381.111111',
                null,
                'updated@example.rs',
                'pw2',
                0,
                new ContactExtension(
                    '999',
                    'Object Creation provided by Oblak Solutions.',
                    null,
                    null,
                    null,
                    null,
                ),
            ),
            'TRID-1',
        );

        self::assertStringContainsString(
            '<contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>OBL-C-1</contact:id>', $xml);
        self::assertStringContainsString('<contact:add><contact:status s="ok"/></contact:add>', $xml);
        self::assertStringContainsString('<contact:rem><contact:status s="linked"/></contact:rem>', $xml);
        self::assertStringContainsString('<contact:chg>', $xml);
        self::assertStringContainsString('<contact:email>updated@example.rs</contact:email>', $xml);
        self::assertStringContainsString('<contactExt:ident>999</contactExt:ident>', $xml);
        self::assertStringContainsString(
            '<contactExt:identDescription>Object Creation provided by Oblak Solutions.</contactExt:identDescription>',
            $xml,
        );
    }

    public function testBuildSerializesExtensionWhenOnlyPolicyCommentIsPresent(): void
    {
        $builder = new ContactUpdateRequestBuilder();

        $xml = $builder->build(
            new ContactUpdateRequest(
                'OBL-C-2',
                [],
                [],
                null,
                null,
                null,
                'updated@example.rs',
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
