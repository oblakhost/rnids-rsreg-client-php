<?php

declare(strict_types=1);

namespace Tests\Unit\Session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Session\Dto\HelloResponse;
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
        $poll = new PollResponse($metadata, 1, 'MSG-1', '2026-02-27T00:00:00.0Z', 'Message');

        self::assertSame([
            'count' => 1,
            'message' => 'Message',
            'messageId' => 'MSG-1',
            'queueDate' => '2026-02-27T00:00:00.0Z',
        ], $mapper->mapPollResponse($poll));

        self::assertSame([], $mapper->mapEmptyResponse());
    }
}
