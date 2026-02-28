<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Xml\Response\ResponseMetadata;
use RNIDS\Xml\Session\PollResponseParser;

#[Group('unit')]
final class PollResponseParserTest extends TestCase
{
    public function testParseMapsQueueDataFromPollResponse(): void
    {
        $parser = new PollResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1301"><msg>Command completed successfully; ack to dequeue</msg></result>'
            . '<msgQ count="2" id="12345">'
            . '<qDate>2026-02-27T00:00:00.0Z</qDate>'
            . '<msg>M100:Domain example.rs registration successful</msg>'
            . '</msgQ>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1301, 'Command completed successfully; ack to dequeue', 'CL-1', 'SV-1'),
        );

        self::assertSame(2, $response->queueCount);
        self::assertSame('12345', $response->messageId);
        self::assertSame('2026-02-27T00:00:00.0Z', $response->queueDate);
        self::assertSame('M100:Domain example.rs registration successful', $response->message);
    }

    public function testParseReturnsNullQueueFieldsWhenMessageQueueIsMissing(): void
    {
        $parser = new PollResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1300"><msg>Command completed successfully; no messages</msg></result>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1300, 'Command completed successfully; no messages', 'CL-1', 'SV-1'),
        );

        self::assertNull($response->queueCount);
        self::assertNull($response->messageId);
        self::assertNull($response->queueDate);
        self::assertNull($response->message);
    }
}
