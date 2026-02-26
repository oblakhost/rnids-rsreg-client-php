<?php

declare(strict_types=1);

namespace RNIDS\Connection;

/**
 * Immutable connection options for the EPP transport.
 */
final class ConnectionConfig
{
    public readonly string $hostname;

    public readonly int $port;

    public readonly int $connectTimeoutSeconds;

    public readonly int $readTimeoutSeconds;

    /**
     * @param string $hostname EPP server hostname.
     * @param int $port EPP server port.
     * @param int $connectTimeoutSeconds Connection timeout in seconds.
     * @param int $readTimeoutSeconds Read timeout in seconds.
     */
    public function __construct(
        string $hostname,
        int $port = 700,
        int $connectTimeoutSeconds = 10,
        int $readTimeoutSeconds = 20,
    ) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->connectTimeoutSeconds = $connectTimeoutSeconds;
        $this->readTimeoutSeconds = $readTimeoutSeconds;
    }
}
