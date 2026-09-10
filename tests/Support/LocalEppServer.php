<?php

declare(strict_types=1);

namespace Tests\Support;

/** A separate PHP process speaks EPP over loopback; it never connects to RNIDS. */
final class LocalEppServer
{
    public int $port = 0;

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    public static function start(string $scenario): self
    {
        if (!\function_exists('proc_open')) {
            throw new \RuntimeException('Local TCP unavailable: proc_open is disabled.');
        }
        $server = new self();
        $server->process = \proc_open(
            [PHP_BINARY, __FILE__, '--serve', $scenario],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $server->pipes,
        );
        if (!\is_resource($server->process)) {
            throw new \RuntimeException('Could not launch the local EPP server process.');
        }
        try {
            $ready = $server->line();
            if (\str_starts_with($ready, 'UNAVAILABLE ')) {
                throw new \RuntimeException('Local TCP unavailable: ' . \substr($ready, 12));
            }
            if (!\preg_match('/^READY ([0-9]+)$/', $ready, $matches)) {
                throw new \RuntimeException('Local EPP server did not publish an endpoint: ' . $ready);
            }
            $server->port = (int) $matches[1];
            return $server;
        } catch (\Throwable $error) {
            $server->stop();
            throw $error;
        }
    }

    /** @return array{commands: list<string>, transactionIds: list<string>, peerClosed?: bool} */
    public function report(): array
    {
        return \json_decode($this->line(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function stop(): void
    {
        if (!\is_resource($this->process)) {
            return;
        }
        foreach ($this->pipes as $pipe) {
            \fclose($pipe);
        }
        $this->pipes = [];
        if (\proc_get_status($this->process)['running']) {
            \proc_terminate($this->process, 9);
        }
        \proc_close($this->process);
        $this->process = null;
    }

    public function __destruct()
    {
        $this->stop();
    }

    private function line(): string
    {
        $read = [$this->pipes[1]];
        $write = null;
        $except = null;
        if (1 !== \stream_select($read, $write, $except, 5)) {
            throw new \RuntimeException('Local EPP server did not respond within five seconds.');
        }
        $line = \fgets($this->pipes[1]);
        if (false === $line) {
            $error = \stream_get_contents($this->pipes[2]);
            throw new \RuntimeException('Local EPP server exited unexpectedly: ' . $error);
        }
        return \trim($line);
    }

    public static function serve(string $scenario): void
    {
        $listener = @\stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if (false === $listener) {
            echo 'UNAVAILABLE loopback socket binding is blocked (code ' . $errno . ').' . PHP_EOL;
            return;
        }
        $endpoint = \stream_socket_get_name($listener, false);
        echo 'READY ' . \substr($endpoint, \strrpos($endpoint, ':') + 1) . PHP_EOL;
        \fflush(STDOUT);
        $peer = \stream_socket_accept($listener, 5);
        \fclose($listener);
        if (false === $peer) {
            throw new \RuntimeException('Local test client did not connect within five seconds.');
        }
        \stream_set_timeout($peer, 5);
        try {
            self::scenario($peer, $scenario);
        } finally {
            \fclose($peer);
        }
    }

    /** @param resource $peer */
    private static function scenario($peer, string $scenario): void
    {
        if ('fragmented' === $scenario) {
            self::frame($peer, \str_repeat('fragmented-payload-', 4096));
            self::frame($peer, 'second-frame');
            return;
        }
        if ('truncated' === $scenario) {
            self::write($peer, \pack('N', 100) . '<epp');
            return;
        }
        if ('silent' === $scenario) {
            // Wait for the parent's cleanup signal instead of sleeping for an arbitrary duration.
            \fgets(STDIN);
            return;
        }
        self::session($peer, 'reject-login' === $scenario);
    }

    /** @param resource $peer */
    private static function session($peer, bool $rejectLogin): void
    {
        self::frame($peer,
            '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><greeting>'
            . '<svID>Local EPP test server</svID><svDate>2026-01-01T00:00:00Z</svDate>'
            . '<svcMenu><version>1.0</version><lang>en</lang>'
            . '<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI></svcMenu>'
            . '</greeting></epp>',
        );
        $report = ['commands' => [], 'transactionIds' => []];
        foreach (['login', 'poll', 'logout'] as $expected) {
            $command = self::readCommand($peer);
            if ($expected !== $command['operation']) {
                throw new \RuntimeException('Expected ' . $expected . ', received ' . $command['operation']);
            }
            $report['commands'][] = $command['operation'];
            $report['transactionIds'][] = $command['transactionId'];
            $code = ['login' => $rejectLogin ? 2200 : 1000, 'poll' => 1300, 'logout' => 1500][$expected];
            self::frame($peer,
                '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response><result code="' . $code . '">'
                . '<msg>Local test response</msg></result><trID><clTRID>'
                . \htmlspecialchars($command['transactionId'], ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</clTRID><svTRID>LOCAL-' . $expected . '</svTRID></trID></response></epp>',
            );
            if ($rejectLogin) {
                $report['peerClosed'] = '' === \fread($peer, 1) && \feof($peer);
                break;
            }
        }
        echo \json_encode($report, JSON_THROW_ON_ERROR) . PHP_EOL;
        \fflush(STDOUT);
    }

    /** @param resource $peer
     * @return array{operation: string, transactionId: string}
     */
    private static function readCommand($peer): array
    {
        $prefix = \unpack('Nlength', self::read($peer, 4));
        $length = $prefix['length'] - 4;
        if ($length < 1 || $length > 1000000) {
            throw new \RuntimeException('Invalid frame length received by local test peer.');
        }
        $document = new \DOMDocument();
        $document->loadXML(self::read($peer, $length));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('epp', 'urn:ietf:params:xml:ns:epp-1.0');
        return [
            'operation' => $xpath->evaluate('local-name(/epp:epp/epp:command/*[1])'),
            'transactionId' => $xpath->evaluate('string(/epp:epp/epp:command/epp:clTRID)'),
        ];
    }

    /** @param resource $peer */
    private static function read($peer, int $length): string
    {
        $bytes = '';
        while (\strlen($bytes) < $length) {
            $chunk = \fread($peer, $length - \strlen($bytes));
            if (false === $chunk || '' === $chunk) {
                throw new \RuntimeException('Local EPP peer received EOF or timed out.');
            }
            $bytes .= $chunk;
        }
        return $bytes;
    }

    /** @param resource $peer */
    private static function frame($peer, string $payload): void
    {
        $frame = \pack('N', \strlen($payload) + 4) . $payload;
        // Separate small writes, including a split length prefix; large payloads exceed read buffers.
        foreach (\str_split($frame, 3) as $chunk) {
            self::write($peer, $chunk);
        }
    }

    /** @param resource $peer */
    private static function write($peer, string $bytes): void
    {
        while ('' !== $bytes) {
            $count = \fwrite($peer, $bytes);
            if (false === $count || 0 === $count) {
                throw new \RuntimeException('Local EPP server could not write to its peer.');
            }
            $bytes = \substr($bytes, $count);
        }
    }
}

if ('--serve' === ($argv[1] ?? null) && \realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    LocalEppServer::serve($argv[2] ?? 'session');
}
