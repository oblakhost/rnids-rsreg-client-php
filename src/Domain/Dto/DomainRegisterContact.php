<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents a contact handle used during domain register command.
 */
final class DomainRegisterContact
{
    /**
     * @param non-empty-string $type
     * @param non-empty-string $handle
     */
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
    ) {
    }
}
