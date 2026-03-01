<?php

declare(strict_types=1);

namespace Tests\Unit\Session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Session\Dto\HelloResponse;
use RNIDS\Session\Dto\PollDomainTransferData;
use RNIDS\Session\Dto\PollResponse;
use RNIDS\Session\SessionResponseMapper;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class SessionResponseMapperTest extends TestCase
{
    public function testMapHelloResponse(): void
    {
        $mapper = new SessionResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');
        $response = new HelloResponse(
            $metadata,
            'RNIDS EPP',
            '2026-02-27T00:00:00.0Z',
            [ '1.0' ],
            [ 'en' ],
            [ 'urn:ietf:params:xml:ns:domain-1.0' ],
            [ 'http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0' ],
        );

        $mapped = $mapper->mapHelloResponse($response);

        self::assertSame('RNIDS EPP', $mapped['serverId']);
        self::assertSame([ '1.0' ], $mapped['versions']);
        self::assertSame([ 'en' ], $mapped['languages']);
    }

    public function testMapPollResponseAndEmptyResponse(): void
    {
        $mapper = new SessionResponseMapper();
        $metadata = new ResponseMetadata(1301, 'Ack', 'CL-1', 'SV-1');
        $poll = new PollResponse(
            $metadata,
            1,
            'MSG-1',
            '2026-02-27T00:00:00.0Z',
            'Message',
            new PollDomainTransferData(
                'example.rs',
                'pending',
                'REG-1',
                '2026-03-01T00:00:00.0Z',
                'REG-2',
                '2026-03-02T00:00:00.0Z',
                '2027-03-01T00:00:00.0Z',
            ),
        );

        self::assertSame([
            'count' => 1,
            'domainTransferData' => [
                'actionClientId' => 'REG-2',
                'actionDate' => '2026-03-02T00:00:00.0Z',
                'expirationDate' => '2027-03-01T00:00:00.0Z',
                'name' => 'example.rs',
                'requestClientId' => 'REG-1',
                'requestDate' => '2026-03-01T00:00:00.0Z',
                'transferStatus' => 'pending',
            ],
            'message' => 'Message',
            'messageId' => 'MSG-1',
            'queueDate' => '2026-02-27T00:00:00.0Z',
        ], $mapper->mapPollResponse($poll));

        self::assertSame([], $mapper->mapEmptyResponse());
    }

    public function testMapPollResponseIncludesNullDomainTransferDataWhenAbsent(): void
    {
        $mapper = new SessionResponseMapper();
        $metadata = new ResponseMetadata(1300, 'No messages', 'CL-1', 'SV-1');
        $poll = new PollResponse(
            $metadata,
            0,
            null,
            null,
            null,
        );

        self::assertSame([
            'count' => 0,
            'domainTransferData' => null,
            'message' => null,
            'messageId' => null,
            'queueDate' => null,
        ], $mapper->mapPollResponse($poll));
    }
}
