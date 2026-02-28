<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostCheckItem
{
    /**
     * Creates a single host availability result item.
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $available,
        public readonly ?string $reason = null,
    ) {
    }
}
