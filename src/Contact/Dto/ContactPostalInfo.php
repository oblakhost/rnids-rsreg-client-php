<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactPostalInfo
{
    public const TYPE_LOC = 'loc';
    public const TYPE_INT = 'int';

    public function __construct(
        public readonly string $type,
        public readonly string $name,
        public readonly ?string $organization,
        public readonly ContactAddress $address,
    ) {
    }
}
