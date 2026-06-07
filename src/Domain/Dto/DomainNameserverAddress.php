<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainNameserverAddress
{
    /**
     * @param non-empty-string $address
     * @param 'v4'|'v6' $ipVersion
     */
    public function __construct(
        public readonly string $address,
        public readonly string $ipVersion,
    ) {
    }
}
