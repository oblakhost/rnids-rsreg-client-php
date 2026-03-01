<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainResponseMapper;
use RNIDS\Domain\Dto\DomainCheckItem;
use RNIDS\Domain\Dto\DomainCheckResponse;
use RNIDS\Domain\Dto\DomainInfoContact;
use RNIDS\Domain\Dto\DomainInfoExtension;
use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
use RNIDS\Domain\Dto\DomainInfoStatus;
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
            [ new DomainInfoStatus('ok', 'Active') ],
            'REG-1',
            [ new DomainInfoContact('admin', 'ADM-1') ],
            [ new DomainInfoNameserver('ns1.example.rs', [ '192.0.2.1' ]) ],
            'CID',
            'CCID',
            'UCID',
            '2024-01-01',
            '2025-01-01',
            '2026-01-01',
            new DomainInfoExtension('1', 'normal', null, null, null),
        );

        $mapped = $mapper->mapInfoResponse($response);

        self::assertSame('example.rs', $mapped['name']);
        self::assertSame('D1-RS', $mapped['roid']);
        self::assertSame('ok', $mapped['statuses'][0]['value']);
        self::assertSame('admin', $mapped['contacts'][0]['type']);
        self::assertSame('ns1.example.rs', $mapped['nameservers'][0]['name']);
        self::assertSame('1', $mapped['extension']['isWhoisPrivacy']);
    }

    public function testMapMutationResponses(): void
    {
        $mapper = new DomainResponseMapper();
        $metadata = new ResponseMetadata(1000, 'ok', 'CL-1', 'SV-1');

        self::assertSame([
            'createDate' => '2024-01-01',
            'expirationDate' => '2025-01-01',
            'name' => 'example.rs',
        ], $mapper->mapRegisterResponse(
            new DomainRegisterResponse($metadata, 'example.rs', '2024-01-01', '2025-01-01'),
        ));

        self::assertSame([
            'expirationDate' => '2026-01-01',
            'name' => 'example.rs',
        ], $mapper->mapRenewResponse(new DomainRenewResponse($metadata, 'example.rs', '2026-01-01')));

        self::assertSame([
            'actionClientId' => 'ACT',
            'actionDate' => '2025-01-01',
            'expirationDate' => '2026-01-01',
            'name' => 'example.rs',
            'requestClientId' => 'REQ',
            'requestDate' => '2024-12-01',
            'transferStatus' => 'pending',
        ], $mapper->mapTransferResponse(new DomainTransferResponse(
            $metadata,
            'example.rs',
            'pending',
            'REQ',
            '2024-12-01',
            'ACT',
            '2025-01-01',
            '2026-01-01',
        )));

        self::assertSame([], $mapper->mapDeleteResponse());
    }
}
