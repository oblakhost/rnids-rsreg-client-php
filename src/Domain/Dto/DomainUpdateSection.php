<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents add/remove update section data for domain update command.
 */
final class DomainUpdateSection
{
    /**
     * @param list<DomainRegisterContact> $contacts
     * @param list<string> $statuses
     */
    public function __construct(
        public readonly array $contacts = [],
        public readonly array $statuses = [],
    ) {
    }
}
