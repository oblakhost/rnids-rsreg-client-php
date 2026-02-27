<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoNameserver
{
    /**
     * @param list<string> $addresses
     */
    public function __construct(
        public readonly string $name,
        public readonly array $addresses = [],
    ) {
    }
}
