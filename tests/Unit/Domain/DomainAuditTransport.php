<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use RNIDS\Connection\Transport;

final class DomainAuditTransport implements Transport
{
    public string $written = '';
    public string $data = '';

    public function connect(): void
    {
        // This deterministic transport has no socket.
    }

    public function disconnect(): void
    {
        // This deterministic transport has no socket.
    }

    public function writeFrame(string $payload): void
    {
        $this->written = $payload;
    }

    public function readFrame(): string
    {
        \preg_match('/<clTRID>([^<]+)<\/clTRID>/', $this->written, $matches);
        return '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response><result code="1000"><msg>OK</msg></result>'
            . $this->data . '<trID><clTRID>' . $matches[1] . '</clTRID><svTRID>SV-1</svTRID></trID></response></epp>';
    }
}
