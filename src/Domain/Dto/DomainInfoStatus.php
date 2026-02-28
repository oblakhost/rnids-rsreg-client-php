<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoStatus
{
    /**
     * @param string $value Domain status value.
     * @param string|null $description Optional human-readable description.
     */
    public function __construct(
        public readonly string $value,
        public readonly ?string $description = null,
    ) {
    }
}
