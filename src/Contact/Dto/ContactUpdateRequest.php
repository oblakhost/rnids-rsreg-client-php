<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactUpdateRequest
{
    /**
     * @param list<string> $addStatuses
     * @param list<string> $removeStatuses
     */
    public function __construct(
        public readonly string $id,
        public readonly array $addStatuses,
        public readonly array $removeStatuses,
        public readonly ?ContactPostalInfo $postalInfo,
        public readonly ?string $voice,
        public readonly ?string $fax,
        public readonly ?string $email,
        public readonly ?string $authInfo,
        public readonly ?int $disclose,
        public readonly ?ContactExtension $extension,
    ) {
    }
}
