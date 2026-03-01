<?php

declare(strict_types=1);

namespace RNIDS\Config;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\TlsConfig;

final class ClientConfig
{
    /** @var list<string> */
    public readonly array $objectUris;

    /** @var list<string> */
    public readonly array $extensionUris;

    /**
     * @param list<string> $objectUris
     * @param list<string> $extensionUris
     */
    public function __construct(
        public readonly ConnectionConfig $connectionConfig,
        public readonly string $username,
        public readonly string $password,
        public readonly string $language,
        public readonly string $version,
        array $objectUris,
        array $extensionUris,
        public readonly ?TlsConfig $tlsConfig,
    ) {
        $this->objectUris = $objectUris;
        $this->extensionUris = $extensionUris;
    }
}
