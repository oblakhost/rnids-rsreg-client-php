<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\Transport;

/**
 * Thin wrapper around the configured transport lifecycle.
 */
final class SocketClient
{
    private Transport $transport;

    /**
     * @param Transport $transport Transport implementation used for EPP socket I/O.
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Opens the underlying transport connection.
     */
    public function connect(): void
    {
        $this->transport->connect();
    }

    /**
     * Closes the underlying transport connection.
     */
    public function disconnect(): void
    {
        $this->transport->disconnect();
    }
}
