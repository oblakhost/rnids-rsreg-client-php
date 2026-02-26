<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainCheckItem
{
    public function __construct(
        public readonly string $name,
        public readonly bool $available,
        public readonly ?string $reason = null,
    ) {
    }
}
