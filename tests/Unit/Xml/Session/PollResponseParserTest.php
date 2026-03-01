<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
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
        self::assertNull($response->domainTransferData);
    }

    public function testParseMapsDomainTransferResDataWhenPresent(): void
    {
        $parser = new PollResponseParser();

        $response = $parser->parse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
            . '<response>'
            . '<result code="1301"><msg>Command completed successfully; ack to dequeue</msg></result>'
            . '<msgQ count="1" id="MSG-TRN-1">'
            . '<qDate>2026-03-01T00:00:00.0Z</qDate>'
            . '<msg>Transfer status notification</msg>'
            . '</msgQ>'
            . '<resData>'
            . '<domain:trnData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name>'
            . '<domain:trStatus>pending</domain:trStatus>'
            . '<domain:reID>REG-1</domain:reID>'
            . '<domain:reDate>2026-03-01T00:00:00.0Z</domain:reDate>'
            . '<domain:acID>REG-2</domain:acID>'
            . '<domain:acDate>2026-03-02T00:00:00.0Z</domain:acDate>'
            . '<domain:exDate>2027-03-01T00:00:00.0Z</domain:exDate>'
            . '</domain:trnData>'
            . '</resData>'
            . '</response>'
            . '</epp>',
            new ResponseMetadata(1301, 'Command completed successfully; ack to dequeue', 'CL-1', 'SV-1'),
        );

        self::assertNotNull($response->domainTransferData);
        $transferData = $response->domainTransferData;
        self::assertSame('example.rs', $transferData->name);
        self::assertSame('pending', $transferData->transferStatus);
        self::assertSame('REG-1', $transferData->requestClientId);
        self::assertSame('2026-03-01T00:00:00.0Z', $transferData->requestDate);
        self::assertSame('REG-2', $transferData->actionClientId);
        self::assertSame('2026-03-02T00:00:00.0Z', $transferData->actionDate);
        self::assertSame('2027-03-01T00:00:00.0Z', $transferData->expirationDate);
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
        self::assertNull($response->domainTransferData);
    }
}
