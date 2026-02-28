<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

final class HostUpdateRequest
{
    public function __construct(
        public readonly string $name,
        public readonly ?HostUpdateSection $add,
        public readonly ?HostUpdateSection $remove,
        public readonly ?string $newName,
    ) {
    }
}
