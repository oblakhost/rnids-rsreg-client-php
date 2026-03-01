<?php

declare(strict_types=1);

namespace RNIDS\Connection;

final class TransportFactory
{
    public function create(ConnectionConfig $connectionConfig, ?TlsConfig $tlsConfig = null): Transport
    {
        return new NativeStreamTransport($connectionConfig, $tlsConfig);
    }
}
