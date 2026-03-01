<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\Dto\ContactCheckRequest;
use RNIDS\Xml\Contact\ContactCheckRequestBuilder;

#[Group('unit')]
final class ContactCheckRequestBuilderTest extends TestCase
{
    public function testBuildCreatesDeterministicContactCheckXml(): void
    {
        $builder = new ContactCheckRequestBuilder();

        $xml = $builder->build(new ContactCheckRequest([ 'C-1', 'C<&>' ]), 'TRID<&>');

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString(
            '<contact:check xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">',
            $xml,
        );
        self::assertStringContainsString('<contact:id>C-1</contact:id>', $xml);
        self::assertStringContainsString('<contact:id>C&lt;&amp;&gt;</contact:id>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
    }
}
