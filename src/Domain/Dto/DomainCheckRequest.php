<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainCheckRequest
{
    /**
     * @param list<string> $names
     */
    public function __construct(public readonly array $names)
    {
    }
}
