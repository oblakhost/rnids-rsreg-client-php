<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Contact\ContactUpdateResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class ContactUpdateResponseParserTest extends TestCase
{
    public function testParseReturnsMetadataBackedResponse(): void
    {
        $parser = new ContactUpdateResponseParser();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');

        $response = $parser->parse('<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"/>', $metadata);

        self::assertSame($metadata, $response->metadata);
    }
}
