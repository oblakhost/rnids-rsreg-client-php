<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactAddress
{
    /**
     * @param list<string> $streets
     */
    public function __construct(
        public readonly array $streets,
        public readonly string $city,
        public readonly string $countryCode,
        public readonly ?string $province,
        public readonly ?string $postalCode,
    ) {
    }
}
