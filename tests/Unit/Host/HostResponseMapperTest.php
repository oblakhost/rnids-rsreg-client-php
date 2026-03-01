<?php

declare(strict_types=1);

namespace Tests\Unit\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostCheckItem;
use RNIDS\Host\Dto\HostCheckResponse;
use RNIDS\Host\Dto\HostCreateResponse;
use RNIDS\Host\Dto\HostInfoResponse;
use RNIDS\Host\Dto\HostStatus;
use RNIDS\Host\HostResponseMapper;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class HostResponseMapperTest extends TestCase
{
    public function testMapCheckResponse(): void
    {
        $mapper = new HostResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');
        $response = new HostCheckResponse($metadata, [
            new HostCheckItem('ns1.example.rs', true, null),
        ]);

        self::assertSame([
            [
                'available' => true,
                'name' => 'ns1.example.rs',
                'reason' => null,
            ],
        ], $mapper->mapCheckResponse($response));
    }

    public function testMapInfoAndCreateResponses(): void
    {
        $mapper = new HostResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');

        $info = new HostInfoResponse(
            $metadata,
            'ns1.example.rs',
            'H-1',
            [ new HostStatus('ok', 'Active') ],
            [ new HostAddress('192.0.2.1', 'v4') ],
            'CID',
            'CCID',
            'UCID',
            '2024-01-01',
            '2025-01-01',
            null,
        );

        $mappedInfo = $mapper->mapInfoResponse($info);
        self::assertSame('ns1.example.rs', $mappedInfo['name']);
        self::assertSame('ok', $mappedInfo['statuses'][0]['value']);
        self::assertSame('192.0.2.1', $mappedInfo['addresses'][0]['address']);

        self::assertSame([
            'createDate' => '2026-02-01T00:00:00.0Z',
            'name' => 'ns1.example.rs',
        ], $mapper->mapCreateResponse(
            new HostCreateResponse($metadata, 'ns1.example.rs', '2026-02-01T00:00:00.0Z'),
        ));
    }

    public function testMapEmptyResponse(): void
    {
        $mapper = new HostResponseMapper();
        self::assertSame([], $mapper->mapEmptyResponse());
    }
}
