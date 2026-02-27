<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoContact
{
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
    ) {
    }
}
