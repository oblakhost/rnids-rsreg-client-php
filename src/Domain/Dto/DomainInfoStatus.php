<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoStatus
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $description = null,
    ) {
    }
}
