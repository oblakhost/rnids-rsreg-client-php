<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto\DomainUpdateResponse;
use RNIDS\Xml\Domain\DomainUpdateResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class DomainUpdateResponseParserTest extends TestCase
{
    public function testParseMapsMetadataIntoDomainUpdateResponse(): void
    {
        $parser = new DomainUpdateResponseParser();
        $metadata = new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1');

        $result = $parser->parse('<epp/>', $metadata);

        self::assertInstanceOf(DomainUpdateResponse::class, $result);
        self::assertSame($metadata, $result->metadata);
    }
}
