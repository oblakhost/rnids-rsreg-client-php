<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainResponseMapper;
use RNIDS\Domain\Dto\DomainCheckItem;
use RNIDS\Domain\Dto\DomainCheckResponse;
use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
use RNIDS\Domain\Dto\DomainRegisterResponse;
use RNIDS\Domain\Dto\DomainRenewResponse;
use RNIDS\Domain\Dto\DomainTransferResponse;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class DomainResponseMapperTest extends TestCase
{
    public function testMapCheckResponse(): void
    {
        $mapper = new DomainResponseMapper();
        $metadata = new ResponseMetadata(1000, 'ok', 'CL-1', 'SV-1');
        $response = new DomainCheckResponse($metadata, [
            new DomainCheckItem('example.rs', true, null),
        ]);

        self::assertSame([
            [
                'available' => true,
                'name' => 'example.rs',
                'reason' => null,
            ],
        ], $mapper->mapCheckResponse($response));
    }

    public function testMapInfoResponse(): void
    {
        $mapper = new DomainResponseMapper();
        $metadata = new ResponseMetadata(1000, 'ok', 'CL-1', 'SV-1');
        $response = new DomainInfoResponse(
            $metadata,
            'example.rs',
            'D1-RS',
            [ 'ok' ],
            'REG-1',
            'ADM-1',
            'TEC-1',
            [ new DomainInfoNameserver('ns1.example.rs', [ '192.0.2.1' ]) ],
            'CID',
            'CCID',
            'UCID',
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            true,
            'normal',
            false,
            true,
            null,
        );

        $mapped = $mapper->mapInfoResponse($response);

        self::assertSame('example.rs', $mapped['name']);
        self::assertSame('D1-RS', $mapped['roid']);
        self::assertSame('ok', $mapped['statuses'][0]);
        self::assertSame('ADM-1', $mapped['adminContact']);
        self::assertSame('ns1.example.rs', \array_key_first($mapped['nameservers']));
        self::assertTrue($mapped['whoisPrivacy']);
    }

    public function testMapMutationResponses(): void
    {
        $mapper = new DomainResponseMapper();
        $metadata = new ResponseMetadata(1000, 'ok', 'CL-1', 'SV-1');

        self::assertEquals([
            'createDate' => new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            'expirationDate' => new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            'name' => 'example.rs',
        ], $mapper->mapRegisterResponse(
            new DomainRegisterResponse(
                $metadata,
                'example.rs',
                new \DateTimeImmutable('2024-01-01T00:00:00Z'),
                new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            ),
        ));

        self::assertEquals([
            'expirationDate' => new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            'name' => 'example.rs',
        ], $mapper->mapRenewResponse(
            new DomainRenewResponse($metadata, 'example.rs', new \DateTimeImmutable('2026-01-01T00:00:00Z')),
        ));

        self::assertEquals([
            'actionClientId' => 'ACT',
            'actionDate' => new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            'expirationDate' => new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            'name' => 'example.rs',
            'requestClientId' => 'REQ',
            'requestDate' => new \DateTimeImmutable('2024-12-01T00:00:00Z'),
            'transferStatus' => 'pending',
        ], $mapper->mapTransferResponse(new DomainTransferResponse(
            $metadata,
            'example.rs',
            'pending',
            'REQ',
            new \DateTimeImmutable('2024-12-01T00:00:00Z'),
            'ACT',
            new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            new \DateTimeImmutable('2026-01-01T00:00:00Z'),
        )));

        self::assertSame([], $mapper->mapDeleteResponse());
    }
}
