<?php

declare(strict_types=1);

namespace Tests\Support;

use RNIDS\Connection\Transport;

/** A local scripted peer which waits for hello and omits response clTRID like RSreg. */
final class CliPeerTransport implements Transport
{
    /** @var list<string> */
    public array $requests = [];

    public bool $connected = false;
    public int $logoutCode = 1500;
    private string $response = '';

    /** @param list<string> $commandResponses */
    public function __construct(private array $commandResponses = [])
    {
    }

    public static function response(string $data = '', int $code = 1000): string
    {
        return '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response>'
            . '<result code="' . $code . '"><msg>Scripted registry result</msg></result>'
            . ($data === '' ? '' : '<resData>' . $data . '</resData>')
            . '<trID><svTRID>SV-CLI</svTRID></trID></response></epp>';
    }

    public function connect(): void
    {
        $this->connected = true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function writeFrame(string $payload): void
    {
        $this->requests[] = $payload;
        $this->response = match (true) {
            str_contains($payload, '<hello/>') => SessionPeerTransport::greeting(),
            str_contains($payload, '<login>') => self::response(),
            str_contains($payload, '<logout/>') => self::response('', $this->logoutCode),
            default => array_shift($this->commandResponses) ?? throw new \RuntimeException('Unexpected command.'),
        };
    }

    public function readFrame(): string
    {
        if ($this->response === '') {
            throw new \RuntimeException('Peer requires hello before greeting.');
        }

        $response = $this->response;
        $this->response = '';
        return $response;
    }
}
