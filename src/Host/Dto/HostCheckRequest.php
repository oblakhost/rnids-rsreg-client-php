<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostCheckRequest
{
    /**
     * @param list<string> $names
     */
    public function __construct(public readonly array $names)
    {
    }
}
