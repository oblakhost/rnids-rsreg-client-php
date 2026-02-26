<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\Transport;

final class SocketClient
{
    private Transport $transport;

    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    public function connect(): void
    {
        $this->transport->connect();
    }

    public function disconnect(): void
    {
        $this->transport->disconnect();
    }
}
