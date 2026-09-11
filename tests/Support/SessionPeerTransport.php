<?php

declare(strict_types=1);

namespace Tests\Support;

use RNIDS\Connection\Transport;

/** A deterministic EPP peer which sends an unsolicited greeting on connection. */
final class SessionPeerTransport implements Transport
{
    /** @var list<string> */
    public array $requests = [];

    /** @var list<string> */
    private array $responses = [];

    public bool $connected = false;
    public bool $omitTransactionIds = false;
    public bool $failRead = false;
    public bool $failConnect = false;
    public bool $failDisconnect = false;
    public ?string $nextResponse = null;

    public static function greeting(): string
    {
        return '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><greeting>'
            . '<svID>RNIDS test peer</svID><svDate>2026-09-10T00:00:00Z</svDate>'
            . '<svcMenu><version>1.0</version><lang>en</lang>'
            . '<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>'
            . '<svcExtension><extURI>urn:ietf:params:xml:ns:secDNS-1.1</extURI></svcExtension>'
            . '</svcMenu></greeting></epp>';
    }

    public static function response(int $code, string $transactionId): string
    {
        return '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response>'
            . '<result code="' . $code . '"><msg>Test peer result</msg></result>'
            . '<trID><clTRID>' . $transactionId . '</clTRID><svTRID>SV-TEST</svTRID></trID>'
            . '</response></epp>';
    }

    public function __construct(public int $loginCode = 1000, public bool $unsolicitedGreeting = true)
    {
    }

    public function connect(): void
    {
        if ($this->failConnect) {
            throw new \RNIDS\Exception\TransportException('Connection failed.');
        }

        $this->connected = true;
        $this->responses = $this->unsolicitedGreeting ? [ self::greeting() ] : [];
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->responses = [];

        if ($this->failDisconnect) {
            throw new \RuntimeException('Disconnect failed.');
        }
    }

    public function writeFrame(string $payload): void
    {
        if (!$this->connected) {
            throw new \RuntimeException('Peer is disconnected.');
        }

        $this->requests[] = $payload;

        if (null !== $this->nextResponse) {
            $this->responses[] = $this->nextResponse;
            $this->nextResponse = null;
            return;
        }

        if (\str_contains($payload, '<hello/>')) {
            $this->responses[] = self::greeting();
            return;
        }

        \preg_match('~<clTRID>([^<]+)</clTRID>~', $payload, $matches);
        $code = \str_contains($payload, '<login>') ? $this->loginCode
            : (\str_contains($payload, '<logout/>') ? 1500 : 1300);
        $response = self::response($code, $matches[1] ?? 'missing-id');
        $this->responses[] = $this->omitTransactionIds
            ? \preg_replace('~<clTRID>[^<]*</clTRID>~', '', $response)
            : $response;
    }

    public function readFrame(): string
    {
        if ($this->failRead) {
            throw new \RuntimeException('Timed out after partial frame.');
        }

        return \array_shift($this->responses) ?? throw new \RuntimeException('No response available.');
    }
}
