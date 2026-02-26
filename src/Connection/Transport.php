<?php

declare(strict_types=1);

namespace RNIDS\Connection;

/**
 * Transport abstraction for EPP frame-oriented communication.
 */
interface Transport
{
    /**
     * Opens the connection to the configured EPP endpoint.
     */
    public function connect(): void;

    /**
     * Closes the connection and releases transport resources.
     */
    public function disconnect(): void;

    /**
     * Writes a single EPP payload frame.
     *
     * @param string $payload XML payload without length prefix.
     */
    public function writeFrame(string $payload): void;

    /**
     * Reads a single EPP payload frame.
     *
     * @return string XML payload without length prefix.
     */
    public function readFrame(): string;
}
