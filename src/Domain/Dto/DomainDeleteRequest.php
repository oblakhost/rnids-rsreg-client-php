<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents input data for EPP domain delete command.
 */
final class DomainDeleteRequest
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(public readonly string $name)
    {
    }
}
