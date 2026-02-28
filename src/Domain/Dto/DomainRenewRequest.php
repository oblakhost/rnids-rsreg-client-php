<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents input data for EPP domain renew command.
 */
final class DomainRenewRequest
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $currentExpirationDate
     * @param non-empty-string $periodUnit
     */
    public function __construct(
        public readonly string $name,
        public readonly string $currentExpirationDate,
        public readonly ?int $period,
        public readonly string $periodUnit = 'y',
    ) {
    }
}
