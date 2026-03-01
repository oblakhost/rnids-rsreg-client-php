<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactCheckItem
{
    public function __construct(
        public readonly string $id,
        public readonly bool $available,
        public readonly ?string $reason = null,
    ) {
    }
}
