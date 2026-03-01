<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class DomainInfoResponse
{
    /**
     * @param list<string> $statuses
     * @param list<DomainInfoNameserver> $nameservers
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?string $roid,
        public readonly array $statuses,
        public readonly ?string $registrant,
        public readonly ?string $adminContact,
        public readonly ?string $techContact,
        public readonly array $nameservers,
        public readonly ?string $clientId,
        public readonly ?string $createClientId,
        public readonly ?string $updateClientId,
        public readonly ?\DateTimeImmutable $createDate,
        public readonly ?\DateTimeImmutable $updateDate,
        public readonly ?\DateTimeImmutable $expirationDate,
        public readonly bool $whoisPrivacy,
        public readonly ?string $operationMode,
        public readonly bool $notifyAdmin,
        public readonly bool $dnsSec,
        public readonly ?string $remark,
    ) {
    }
}
