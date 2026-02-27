<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents a nameserver definition used during domain register command.
 */
final class DomainRegisterNameserver
{
    /**
     * @param non-empty-string $name
     * @param list<string> $addresses
     */
    public function __construct(
        public readonly string $name,
        public readonly array $addresses = [],
    ) {
    }
}
