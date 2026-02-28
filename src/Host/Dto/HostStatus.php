<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostStatus
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $description = null,
    ) {
    }
}
