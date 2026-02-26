<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Connection\TlsConfig;
use RNIDS\Connection\Transport;

final class Builder
{
    private ConnectionConfig $connectionConfig;

    private ?TlsConfig $tlsConfig = null;

    public function __construct(?ConnectionConfig $connectionConfig = null)
    {
        $this->connectionConfig = $connectionConfig ?? new ConnectionConfig('localhost');
    }

    public function withConnectionConfig(ConnectionConfig $connectionConfig): self
    {
        $this->connectionConfig = $connectionConfig;

        return $this;
    }

    public function withTlsConfig(?TlsConfig $tlsConfig): self
    {
        $this->tlsConfig = $tlsConfig;

        return $this;
    }

    public function buildTransport(): Transport
    {
        return new NativeStreamTransport($this->connectionConfig, $this->tlsConfig);
    }
}
