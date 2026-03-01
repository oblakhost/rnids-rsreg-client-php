<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class ContactInfoResponse
{
    /**
     * @param list<string> $statuses
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $id,
        public readonly ?string $roid,
        public readonly array $statuses,
        public readonly ?ContactPostalInfo $postalInfo,
        public readonly ?string $voice,
        public readonly ?string $fax,
        public readonly ?string $email,
        public readonly ?string $clientId,
        public readonly ?string $createClientId,
        public readonly ?string $updateClientId,
        public readonly ?\DateTimeImmutable $createDate,
        public readonly ?\DateTimeImmutable $updateDate,
        public readonly ?\DateTimeImmutable $transferDate,
        public readonly ?int $disclose,
        public readonly ?string $ident,
        public readonly ?string $identDescription,
        public readonly ?string $identExpiry,
        public readonly ?string $identKind,
        public readonly bool $legalEntity,
        public readonly ?string $vatNo,
    ) {
    }
}
