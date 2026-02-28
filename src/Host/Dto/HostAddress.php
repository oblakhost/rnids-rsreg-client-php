<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostAddress
{
    /**
     * Creates a host address value object.
     */
    public function __construct(
        public readonly string $address,
        public readonly string $ipVersion = 'v4',
    ) {
    }
}
