<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents input data for EPP domain register (create) command.
 */
final class DomainRegisterRequest
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $periodUnit
     * @param list<DomainRegisterNameserver> $nameservers
     * @param non-empty-string $registrant
     * @param list<DomainRegisterContact> $contacts
     * @param ?non-empty-string $authInfo
     */
    public function __construct(
        public readonly string $name,
        public readonly ?int $period,
        public readonly string $periodUnit,
        public readonly array $nameservers,
        public readonly string $registrant,
        public readonly array $contacts,
        public readonly ?string $authInfo,
        public readonly ?DomainRegisterExtension $extension,
    ) {
    }
}
