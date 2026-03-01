<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactExtension
{
    public function __construct(
        public readonly ?string $ident,
        public readonly ?string $identDescription,
        public readonly ?string $identExpiry,
        public readonly ?string $identKind,
        public readonly ?string $isLegalEntity,
        public readonly ?string $vatNo,
    ) {
    }
}
