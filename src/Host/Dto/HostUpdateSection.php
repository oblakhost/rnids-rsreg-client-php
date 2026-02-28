<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostUpdateSection
{
    /**
     * @param list<HostAddress> $addresses
     * @param list<string> $statuses
     */
    public function __construct(
        public readonly array $addresses,
        public readonly array $statuses,
    ) {
    }
}
