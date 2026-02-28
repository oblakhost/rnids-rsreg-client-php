<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Host\HostUpdateResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class HostUpdateResponseParserTest extends TestCase
{
    public function testParseMapsResponseMetadata(): void
    {
        $parser = new HostUpdateResponseParser();
        $metadata = new ResponseMetadata(1000, 'Command completed successfully', 'CL-1', 'SV-1');

        $response = $parser->parse('<?xml version="1.0"?><epp/>', $metadata);

        self::assertSame($metadata, $response->metadata);
    }
}
