<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\Dto\ContactDeleteRequest;
use RNIDS\Xml\Contact\ContactDeleteRequestBuilder;

#[Group('unit')]
final class ContactDeleteRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicContactDeleteXml(): void
    {
        $builder = new ContactDeleteRequestBuilder();

        $xml = $builder->build(new ContactDeleteRequest('C-100'), 'TRID-100');

        self::assertStringContainsString(
            '<contact:delete xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>C-100</contact:id>', $xml);
        self::assertStringContainsString('<clTRID>TRID-100</clTRID>', $xml);
    }
}
