<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactStatus
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $description = null,
    ) {
    }
}
