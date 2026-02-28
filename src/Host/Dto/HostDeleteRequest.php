<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostDeleteRequest
{
    /**
     * Creates a host delete request DTO.
     */
    public function __construct(public readonly string $name)
    {
    }
}
