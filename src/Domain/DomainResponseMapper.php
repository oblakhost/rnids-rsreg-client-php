<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Domain\Dto\DomainCheckItem;
use RNIDS\Domain\Dto\DomainCheckResponse;
use RNIDS\Domain\Dto\DomainInfoContact;
use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
use RNIDS\Domain\Dto\DomainInfoStatus;
use RNIDS\Domain\Dto\DomainRegisterResponse;
use RNIDS\Domain\Dto\DomainRenewResponse;
use RNIDS\Domain\Dto\DomainTransferResponse;

final class DomainResponseMapper
{
    /**
     * @return list<array{name: string, available: bool, reason: string|null}>
     */
    public function mapCheckResponse(DomainCheckResponse $response): array
    {
        return \array_map(
            static fn(DomainCheckItem $item): array => [
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
     *   registrant: string|null,
     *   contacts: list<array{type: string, handle: string}>,
     *   nameservers: list<array{name: string, addresses: list<string>}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: string|null,
     *   updateDate: string|null,
     *   expirationDate: string|null,
     *   extension: array{
     *     isWhoisPrivacy: string|null,
     *     operationMode: string|null,
     *     notifyAdmin: string|null,
     *     dnsSec: string|null,
     *     remark: string|null
     *   }
     * }
     */
    public function mapInfoResponse(DomainInfoResponse $response): array
    {
        return [
            'clientId' => $response->clientId,
            'contacts' => \array_map(
                static fn(DomainInfoContact $contact): array => [
                    'handle' => $contact->handle,
                    'type' => $contact->type,
                ],
                $response->contacts,
            ),
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'expirationDate' => $response->expirationDate,
            'extension' => [
                'dnsSec' => $response->extension->dnsSec,
                'isWhoisPrivacy' => $response->extension->isWhoisPrivacy,
                'notifyAdmin' => $response->extension->notifyAdmin,
                'operationMode' => $response->extension->operationMode,
                'remark' => $response->extension->remark,
            ],
            'name' => $response->name,
            'nameservers' => \array_map(
                static fn(DomainInfoNameserver $nameserver): array => [
                    'addresses' => $nameserver->addresses,
                    'name' => $nameserver->name,
                ],
                $response->nameservers,
            ),
            'registrant' => $response->registrant,
            'roid' => $response->roid,
            'statuses' => \array_map(
                static fn(DomainInfoStatus $status): array => [
                    'description' => $status->description,
                    'value' => $status->value,
                ],
                $response->statuses,
            ),
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
        ];
    }

    /**
     * @return array{name: string|null, createDate: string|null, expirationDate: string|null}
     */
    public function mapRegisterResponse(DomainRegisterResponse $response): array
    {
        return [
            'createDate' => $response->createDate,
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
        ];
    }

    /**
     * @return array{name: string|null, expirationDate: string|null}
     */
    public function mapRenewResponse(DomainRenewResponse $response): array
    {
        return [
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
        ];
    }

    /**
     * @return array{}
     */
    public function mapDeleteResponse(): array
    {
        return [];
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: string|null,
     *   actionClientId: string|null,
     *   actionDate: string|null,
     *   expirationDate: string|null
     * }
     */
    public function mapTransferResponse(DomainTransferResponse $response): array
    {
        return [
            'actionClientId' => $response->actionClientId,
            'actionDate' => $response->actionDate,
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
            'requestClientId' => $response->requestClientId,
            'requestDate' => $response->requestDate,
            'transferStatus' => $response->transferStatus,
        ];
    }
}
