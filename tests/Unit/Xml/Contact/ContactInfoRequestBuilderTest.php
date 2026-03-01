<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\Dto\ContactInfoRequest;
use RNIDS\Xml\Contact\ContactInfoRequestBuilder;

#[Group('unit')]
final class ContactInfoRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicContactInfoXml(): void
    {
        $builder = new ContactInfoRequestBuilder();

        $xml = $builder->build(new ContactInfoRequest('C<&>'), 'TRID<&>');

        self::assertStringContainsString(
            '<contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>C&lt;&amp;&gt;</contact:id>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
