<?php

declare(strict_types=1);

namespace RNIDS\Connection;

final class ConnectionConfig
{
    public readonly string $hostname;

    public readonly int $port;

    public readonly int $connectTimeoutSeconds;

    public readonly int $readTimeoutSeconds;

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
