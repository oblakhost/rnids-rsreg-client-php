<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainDnssecUpdate
{
    /**
     * @param list<DomainDsRecord> $add
     * @param list<DomainDsRecord> $remove
     */
    public function __construct(
        public readonly array $add = [],
        public readonly array $remove = [],
        public readonly bool $removeAll = false,
    ) {
        if ($removeAll && [] !== $remove) {
            throw new \InvalidArgumentException(
                'DNSSEC removeAll and individual removals are mutually exclusive.',
            );
        }
        if (!$removeAll && [] === $add && [] === $remove) {
            throw new \InvalidArgumentException('DNSSEC update requires a DS record change.');
        }
    }
}
