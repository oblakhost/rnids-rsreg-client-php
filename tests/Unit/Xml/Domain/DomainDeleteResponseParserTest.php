<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Domain\DomainDeleteResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class DomainDeleteResponseParserTest extends TestCase
{
    public function testParseMapsResponseMetadata(): void
    {
        $parser = new DomainDeleteResponseParser();
        $metadata = new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1');

        $response = $parser->parse('<?xml version="1.0"?><epp/>', $metadata);

        self::assertSame($metadata, $response->metadata);
    }
}
