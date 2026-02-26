<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Connection\TlsConfig;
use RNIDS\Connection\Transport;

/**
 * Fluent builder for creating configured transport instances.
 */
final class Builder
{
    private ConnectionConfig $connectionConfig;

    private ?TlsConfig $tlsConfig = null;

    /**
     * @param ConnectionConfig|null $connectionConfig Explicit connection config or a localhost default.
     */
    public function __construct(?ConnectionConfig $connectionConfig = null)
    {
        $this->connectionConfig = $connectionConfig ?? new ConnectionConfig('localhost');
    }

    /**
     * Sets the connection configuration used by the builder.
     */
    public function withConnectionConfig(ConnectionConfig $connectionConfig): self
    {
        $this->connectionConfig = $connectionConfig;

        return $this;
    }

    /**
     * Sets optional TLS configuration used by the builder.
     */
    public function withTlsConfig(?TlsConfig $tlsConfig): self
    {
        $this->tlsConfig = $tlsConfig;

        return $this;
    }

    /**
     * Builds a native stream transport from collected configuration.
     */
    public function buildTransport(): Transport
    {
        return new NativeStreamTransport($this->connectionConfig, $this->tlsConfig);
    }
}
