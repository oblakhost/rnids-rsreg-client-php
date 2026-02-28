<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoContact
{
    /**
     * @param string $type Contact role (for example admin, tech, billing).
     * @param string $handle Contact identifier.
     */
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
    ) {
    }
}
