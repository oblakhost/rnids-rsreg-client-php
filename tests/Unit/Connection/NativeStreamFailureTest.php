<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Exception\TransportException;

final class NativeStreamFailureTest extends TestCase
{
    public function testOversizedFrameClosesTheConnection(): void
    {
        $stream = \fopen('php://temp', 'r+b');
        \fwrite($stream, \pack('N', 1000005));
        \rewind($stream);
        $transport = $this->transport($stream);

        try {
            $transport->readFrame();
            self::fail('Oversized frame accepted.');
        } catch (\RuntimeException) {
            self::assertFalse(\is_resource($stream));
        }
    }

    public function testPartialFrameTimeoutClosesConnectionAndMapsTimeout(): void
    {
        [ $stream, $peer ] = \stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        \stream_set_timeout($stream, 0, 1000);
        if (11 !== @\fwrite($peer, \pack('N', 50) . 'partial')) {
            \fclose($peer);
            \fclose($stream);
            self::markTestSkipped('Local socket writes are unavailable in this environment.');
        }
        $transport = $this->transport($stream);

        try {
            $transport->readFrame();
            self::fail('Partial frame timeout accepted.');
        } catch (\RNIDS\Exception\TransportException $exception) {
            self::assertStringContainsString('Timed out', $exception->getMessage());
            self::assertFalse(\is_resource($stream));
        } finally {
            \fclose($peer);
        }
    }

    public function testPartialFrameEofClosesConnectionAndMapsEof(): void
    {
        $stream = \fopen('php://temp', 'r+b');
        \fwrite($stream, \pack('N', 50) . 'partial');
        \rewind($stream);
        $transport = $this->transport($stream);

        try {
            $transport->readFrame();
            self::fail('Partial frame EOF accepted.');
        } catch (\RNIDS\Exception\TransportException $exception) {
            self::assertStringContainsString('EOF', $exception->getMessage());
            self::assertFalse(\is_resource($stream));
        }
    }

    /** @param resource $stream */
    private function transport($stream): NativeStreamTransport
    {
        $transport = new NativeStreamTransport(new ConnectionConfig('localhost'));
        (new \ReflectionProperty($transport, 'connection'))->setValue($transport, $stream);
        return $transport;
    }
}
