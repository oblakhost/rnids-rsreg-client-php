<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Domain\Dto\DomainCheckItem;
use RNIDS\Domain\Dto\DomainCheckResponse;
use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
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
     *   statuses: list<string>,
     *   registrant: string|null,
     *   adminContact: string|null,
     *   techContact: string|null,
     *   nameservers: array<string, array{ipv4: list<string>, ipv6: list<string>}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: \DateTimeImmutable|null,
     *   updateDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null,
     *   whoisPrivacy: bool,
     *   isDomainVerified: bool,
     *   domainVerifiedOn: \DateTimeImmutable|null,
     *   domainVerificationRequestExpiresOn: \DateTimeImmutable|null,
     *   isWhoisPrivacyPaid: bool,
     *   operationMode: string|null,
     *   notifyAdmin: bool,
     *   dnsSec: bool,
     *   remark: string|null
     * }
     */
    public function mapInfoResponse(DomainInfoResponse $response): array
    {
        return [
            'adminContact' => $response->adminContact,
            'clientId' => $response->clientId,
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'dnsSec' => $response->dnsSec,
            'domainVerificationRequestExpiresOn' => $response->domainVerificationRequestExpiresOn,
            'domainVerifiedOn' => $response->domainVerifiedOn,
            'expirationDate' => $response->expirationDate,
            'isDomainVerified' => $response->isDomainVerified,
            'isWhoisPrivacyPaid' => $response->isWhoisPrivacyPaid,
            'name' => $response->name,
            'nameservers' => $this->mapNameservers($response->nameservers),
            'notifyAdmin' => $response->notifyAdmin,
            'operationMode' => $response->operationMode,
            'registrant' => $response->registrant,
            'remark' => $response->remark,
            'roid' => $response->roid,
            'statuses' => $response->statuses,
            'techContact' => $response->techContact,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
            'whoisPrivacy' => $response->whoisPrivacy,
        ];
    }

    /**
     * @return array{name: string|null, createDate: \DateTimeImmutable|null, expirationDate: \DateTimeImmutable|null}
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
     * @return array{name: string|null, expirationDate: \DateTimeImmutable|null}
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
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
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

    /**
     * @param list<DomainInfoNameserver> $nameservers
     *
     * @return array<string, array{ipv4: list<string>, ipv6: list<string>}>
     */
    private function mapNameservers(array $nameservers): array
    {
        $mapped = [];

        foreach ($nameservers as $nameserver) {
            $mapped[$nameserver->name] = [
                'ipv4' => [],
                'ipv6' => [],
            ];

            foreach ($nameserver->addresses as $address) {
                if (\str_contains($address, ':')) {
                    $mapped[$nameserver->name]['ipv6'][] = $address;

                    continue;
                }

                $mapped[$nameserver->name]['ipv4'][] = $address;
            }
        }

        return $mapped;
    }
}
