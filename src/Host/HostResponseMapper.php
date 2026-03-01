<?php

declare(strict_types=1);

namespace RNIDS\Host;

use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostCheckItem;
use RNIDS\Host\Dto\HostCheckResponse;
use RNIDS\Host\Dto\HostCreateResponse;
use RNIDS\Host\Dto\HostInfoResponse;
use RNIDS\Host\Dto\HostStatus;

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
     *   statuses: list<array{value: string, description: string|null}>,
     *   addresses: list<array{address: string, ipVersion: string}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: string|null,
     *   updateDate: string|null,
     *   transferDate: string|null
     * }
     */
    public function mapInfoResponse(HostInfoResponse $response): array
    {
        return [
            'addresses' => \array_map(
                static fn(HostAddress $address): array => [
                    'address' => $address->address,
                    'ipVersion' => $address->ipVersion,
                ],
                $response->addresses,
            ),
            'clientId' => $response->clientId,
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'name' => $response->name,
            'roid' => $response->roid,
            'statuses' => \array_map(
                static fn(HostStatus $status): array => [
                    'description' => $status->description,
                    'value' => $status->value,
                ],
                $response->statuses,
            ),
            'transferDate' => $response->transferDate,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
        ];
    }

    /**
     * @return array{name: string|null, createDate: string|null}
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
