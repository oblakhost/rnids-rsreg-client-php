<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostDeleteRequest
{
    public function __construct(public readonly string $name)
    {
    }
}
