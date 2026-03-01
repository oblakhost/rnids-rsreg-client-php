<?php

declare(strict_types=1);

namespace RNIDS\Host;

use RNIDS\Host\Dto\HostCheckItem;
use RNIDS\Host\Dto\HostCheckResponse;
use RNIDS\Host\Dto\HostCreateResponse;
use RNIDS\Host\Dto\HostInfoResponse;

final class HostResponseMapper
{
    /**
     * @return list<array{name: string, available: bool, reason: string|null}>
     */
    public function mapCheckResponse(HostCheckResponse $response): array
    {
        return \array_map(
            static fn(HostCheckItem $item): array => [
                'available' => $item->available,
                'name' => $item->name,
                'reason' => $item->reason,
            ],
            $response->items,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   roid: string|null,
     *   statuses: list<string>,
     *   ipv4: list<string>,
     *   ipv6: list<string>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: \DateTimeImmutable|null,
     *   updateDate: \DateTimeImmutable|null,
     *   transferDate: \DateTimeImmutable|null
     * }
     */
    public function mapInfoResponse(HostInfoResponse $response): array
    {
        return [
            'clientId' => $response->clientId,
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'ipv4' => $response->ipv4,
            'ipv6' => $response->ipv6,
            'name' => $response->name,
            'roid' => $response->roid,
            'statuses' => $response->statuses,
            'transferDate' => $response->transferDate,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
        ];
    }

    /**
     * @return array{name: string|null, createDate: \DateTimeImmutable|null}
     */
    public function mapCreateResponse(HostCreateResponse $response): array
    {
        return [
            'createDate' => $response->createDate,
            'name' => $response->name,
        ];
    }

    /**
     * @return array{}
     */
    public function mapEmptyResponse(): array
    {
        return [];
    }
}
