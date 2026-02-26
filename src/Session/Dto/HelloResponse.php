<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * DTO representing an EPP greeting (hello) response.
 */
final class HelloResponse
{
    /**
     * @param list<string> $versions
     * @param list<string> $languages
     * @param list<string> $objectUris
     * @param list<string> $extensionUris
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $serverId,
        public readonly ?string $serverDate,
        public readonly array $versions,
        public readonly array $languages,
        public readonly array $objectUris,
        public readonly array $extensionUris,
    ) {
    }
}
