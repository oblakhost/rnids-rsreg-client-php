<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

final class LoginRequest
{
    /**
     * @param list<string> $objectUris
     * @param list<string> $extensionUris
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $password,
        public readonly string $version = '1.0',
        public readonly string $language = 'en',
        public readonly array $objectUris = [],
        public readonly array $extensionUris = [],
    ) {
    }
}
