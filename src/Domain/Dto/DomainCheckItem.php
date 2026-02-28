<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainCheckItem
{
    /**
     * @param string $name Domain name.
     * @param bool $available Domain availability flag.
     * @param string|null $reason Optional registry reason when unavailable.
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $available,
        public readonly ?string $reason = null,
    ) {
    }
}
