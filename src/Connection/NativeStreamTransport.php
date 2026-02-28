<?php

declare(strict_types=1);

namespace RNIDS\Connection;

final class NativeStreamTransport implements Transport
{
    private EppFrameCodec $frameCodec;

    /**
     * @var resource|null
     */
    private $connection = null;

    /**
     * @param EppFrameCodec|null $frameCodec Optional codec override for testing.
     */
    public function __construct(
        private readonly ConnectionConfig $connectionConfig,
        private readonly ?TlsConfig $tlsConfig = null,
        ?EppFrameCodec $frameCodec = null,
    ) {
        $this->frameCodec = $frameCodec ?? new EppFrameCodec();
    }

    /**
     * Opens a socket connection and applies configured runtime options.
     */
    public function connect(): void
    {
        if (\is_resource($this->connection)) {
            return;
        }

        $scheme = null !== $this->tlsConfig ? 'tls' : 'tcp';
        $target = \sprintf(
            '%s://%s:%d',
            $scheme,
            $this->connectionConfig->hostname,
            $this->connectionConfig->port,
        );
        $context = $this->buildContext();

        $errorCode = 0;
        $errorMessage = '';

        $connection = @\stream_socket_client(
            $target,
            $errorCode,
            $errorMessage,
            $this->connectionConfig->connectTimeoutSeconds,
            \STREAM_CLIENT_CONNECT,
            $context,
        );

        if (false === $connection) {
            throw new \RuntimeException(
                \sprintf('Failed to connect to %s: %s (code %d)', $target, $errorMessage, $errorCode),
            );
        }

        \stream_set_blocking($connection, true);
        \stream_set_timeout($connection, $this->connectionConfig->readTimeoutSeconds);

        $this->connection = $connection;
    }

    /**
     * Closes the active socket connection when present.
     */
    public function disconnect(): void
    {
        if (!\is_resource($this->connection)) {
            return;
        }

        \fclose($this->connection);
        $this->connection = null;
    }

    /**
     * Writes one full EPP frame to the stream.
     */
    public function writeFrame(string $payload): void
    {
        $connection = $this->requireConnection();
        $frame = $this->frameCodec->encode($payload);
        $remaining = \strlen($frame);
        $writtenBytes = 0;

        while ($remaining > 0) {
            $written = \fwrite($connection, \substr($frame, $writtenBytes, $remaining));

            if (false === $written) {
                throw new \RuntimeException('Failed to write to EPP transport stream.');
            }

            if (0 === $written) {
                throw new \RuntimeException('EPP transport stream returned zero bytes written.');
            }

            $writtenBytes += $written;
            $remaining -= $written;
        }
    }

    /**
     * Reads and decodes one EPP frame payload from the stream.
     */
    public function readFrame(): string
    {
        $connection = $this->requireConnection();
        $prefix = $this->readExactBytes($connection, 4);
        $payloadLength = $this->frameCodec->decodeLengthPrefix($prefix);

        if ($payloadLength < 0) {
            throw new \RuntimeException('Invalid EPP payload length (negative value).');
        }

        return $this->readExactBytes($connection, $payloadLength);
    }

    /**
     * @param resource $connection
     */
    private function readExactBytes($connection, int $length): string
    {
        $buffer = '';

        while (\strlen($buffer) < $length) {
            $chunk = \fread($connection, $length - \strlen($buffer));

            if (false === $chunk) {
                throw new \RuntimeException('Failed reading from EPP transport stream.');
            }

            if ('' !== $chunk) {
                $buffer .= $chunk;

                continue;
            }

            $this->assertConnectionStateAfterEmptyRead($connection);
        }

        return $buffer;
    }

    /**
     * @param resource $connection
     */
    private function assertConnectionStateAfterEmptyRead($connection): void
    {
        if (\feof($connection)) {
            throw new \RuntimeException('Unexpected EOF while reading EPP frame.');
        }

        if (true === \stream_get_meta_data($connection)['timed_out']) {
            throw new \RuntimeException('Timed out while reading EPP frame.');
        }
    }

    /**
     * @return resource
     */
    private function requireConnection()
    {
        if (!\is_resource($this->connection)) {
            throw new \RuntimeException('Transport is not connected.');
        }

        return $this->connection;
    }

    /**
     * @return resource
     */
    private function buildContext()
    {
        $context = \stream_context_create();

        if (null === $this->tlsConfig) {
            return $context;
        }

        \stream_context_set_option($context, 'ssl', 'local_cert', $this->tlsConfig->clientCertificatePath);

        if (null !== $this->tlsConfig->clientCertificatePassword) {
            \stream_context_set_option(
                $context,
                'ssl',
                'passphrase',
                $this->tlsConfig->clientCertificatePassword,
            );
        }

        if (null !== $this->tlsConfig->caFilePath) {
            \stream_context_set_option($context, 'ssl', 'cafile', $this->tlsConfig->caFilePath);
        }

        if (null !== $this->tlsConfig->peerName) {
            \stream_context_set_option($context, 'ssl', 'peer_name', $this->tlsConfig->peerName);
        }

        \stream_context_set_option($context, 'ssl', 'allow_self_signed', $this->tlsConfig->allowSelfSigned);

        return $context;
    }
}
