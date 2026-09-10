<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainDnssecCreate
{
    /** @param list<DomainDsRecord> $records */
    public function __construct(public readonly array $records)
    {
        if ([] === $records) {
            throw new \InvalidArgumentException('DNSSEC create requires at least one DS record.');
        }
    }
}
