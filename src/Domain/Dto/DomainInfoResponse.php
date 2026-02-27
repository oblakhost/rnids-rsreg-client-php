<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class DomainInfoResponse
{
    /**
     * @param list<DomainInfoStatus> $statuses
     * @param list<DomainInfoContact> $contacts
     * @param list<DomainInfoNameserver> $nameservers
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?string $roid,
        public readonly array $statuses,
        public readonly ?string $registrant,
        public readonly array $contacts,
        public readonly array $nameservers,
        public readonly ?string $clientId,
        public readonly ?string $createClientId,
        public readonly ?string $updateClientId,
        public readonly ?string $createDate,
        public readonly ?string $updateDate,
        public readonly ?string $expirationDate,
        public readonly DomainInfoExtension $extension,
    ) {
    }
}
