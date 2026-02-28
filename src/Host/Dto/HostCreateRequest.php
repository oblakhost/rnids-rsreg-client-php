<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostCreateRequest
{
    /**
     * @param list<HostAddress> $addresses
     */
    public function __construct(
        public readonly string $name,
        public readonly array $addresses,
    ) {
    }
}
