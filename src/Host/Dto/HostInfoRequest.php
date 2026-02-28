<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostInfoRequest
{
    /**
     * Creates a host info request DTO.
     */
    public function __construct(public readonly string $name)
    {
    }
}
