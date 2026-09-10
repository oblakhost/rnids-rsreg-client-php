<?php

declare(strict_types=1);

namespace RNIDS\Session;

/** Shared connection state for the fluent services of a client. */
final class SessionState
{
    private bool $connected = false;

    private bool $authenticated = false;

    public function connect(): void
    {
        $this->connected = true;
        $this->authenticated = false;
    }

    public function authenticate(): void
    {
        $this->authenticated = true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->authenticated = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }
}
