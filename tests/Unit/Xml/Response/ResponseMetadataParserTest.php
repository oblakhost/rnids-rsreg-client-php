<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Response;

use PHPUnit\Framework\TestCase;
use RNIDS\Exception\MalformedResponseException;
use RNIDS\Xml\Response\ResponseMetadataParser;

/**
 * Unit tests for extracting response metadata from EPP XML.
 */
final class ResponseMetadataParserTest extends TestCase
{
    /**
     * Verifies parser extracts result fields and transaction identifiers.
     */
    public function testParseExtractsResultAndTransactionMetadata(): void
    {
        $parser = new ResponseMetadataParser();

        $metadata = $parser->parse('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1000"><msg>Command completed successfully</msg></result>'
            . '<trID><clTRID>ABC-1</clTRID><svTRID>SV-1</svTRID></trID>'
            . '</response>'
            . '</epp>');

        self::assertSame(1000, $metadata->resultCode);
        self::assertSame('Command completed successfully', $metadata->message);
        self::assertSame('ABC-1', $metadata->clientTransactionId);
        self::assertSame('SV-1', $metadata->serverTransactionId);
    }

    /**
     * Verifies greeting frames are mapped to synthetic successful metadata.
     */
    public function testParseMapsGreetingToSyntheticSuccessMetadata(): void
    {
        $parser = new ResponseMetadataParser();

        $metadata = $parser->parse('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<greeting><svID>RNIDS EPP</svID></greeting>'
            . '</epp>');

        self::assertSame(1000, $metadata->resultCode);
        self::assertSame('Greeting', $metadata->message);
        self::assertNull($metadata->clientTransactionId);
        self::assertNull($metadata->serverTransactionId);
    }

    /**
     * Verifies malformed responses without result code or greeting throw.
     */
    public function testParseThrowsWhenResultCodeAndGreetingAreMissing(): void
    {
        $parser = new ResponseMetadataParser();

        $this->expectException(\RNIDS\Exception\MalformedResponseException::class);
        $this->expectExceptionMessage('Unable to parse EPP result code from response XML.');

        $parser->parse('<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response><msgQ count="0"/></response>'
            . '</epp>');
    }
}
