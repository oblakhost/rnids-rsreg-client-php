<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Connection\TlsConfig;

#[Group('unit')]
final class NativeStreamTransportTest extends TestCase
{
    public function testConnectFailsWhenClientCertificateFileIsNotReadable(): void
    {
        $transport = new NativeStreamTransport(
            new ConnectionConfig('epp-test.rnids.rs', 700, 1, 1),
            new TlsConfig('/this/path/does/not/exist/client.pem'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TLS client certificate file is not readable');

        $transport->connect();
    }

    public function testConnectFailsWhenConfiguredCaFileIsNotReadable(): void
    {
        $transport = new NativeStreamTransport(
            new ConnectionConfig('epp-test.rnids.rs', 700, 1, 1),
            new TlsConfig(
                __DIR__ . '/../../fixtures/oblak.pem',
                null,
                '/this/path/does/not/exist/ca.pem',
            ),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TLS CA certificate file is not readable');

        $transport->connect();
    }

    public function testReadFrameRejectsOversizedPayloadLength(): void
    {
        $transport = new NativeStreamTransport(new ConnectionConfig('localhost'));
        $stream = \fopen('php://temp', 'r+b');

        if (false === $stream) {
            self::fail('Unable to open in-memory stream for test.');
        }

        \fwrite($stream, \pack('N', 1000005));
        \rewind($stream);

        $connection = new \ReflectionProperty($transport, 'connection');
        $connection->setAccessible(true);
        $connection->setValue($transport, $stream);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Packet size is too big: 1000001. Closing connection.');

        try {
            $transport->readFrame();
        } finally {
            \fclose($stream);
        }
    }

    public function testConnectFailureMessageUsesLegacySslSchemeWhenTlsEnabled(): void
    {
        $transport = new NativeStreamTransport(
            new ConnectionConfig('invalid.host.for.rnids.test', 700, 1, 1),
            new TlsConfig(__DIR__ . '/../../fixtures/oblak.pem'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ssl://invalid.host.for.rnids.test:700');

        $transport->connect();
    }

    public function testTlsContextSkipsVerifyOptionsWhenNotConfigured(): void
    {
        $transport = new NativeStreamTransport(
            new ConnectionConfig('epp-test.rnids.rs'),
            new TlsConfig(
                '/tmp/client.pem',
                'secret',
                '/tmp/ca.pem',
                '*.rnids.rs',
                true,
            ),
        );

        $context = $this->invokeBuildContext($transport);
        $options = \stream_context_get_options($context);

        self::assertSame('/tmp/client.pem', $options['ssl']['local_cert']);
        self::assertSame('secret', $options['ssl']['passphrase']);
        self::assertSame('/tmp/ca.pem', $options['ssl']['cafile']);
        self::assertSame('*.rnids.rs', $options['ssl']['peer_name']);
        self::assertTrue($options['ssl']['allow_self_signed']);
        self::assertArrayNotHasKey('verify_peer', $options['ssl']);
        self::assertArrayNotHasKey('verify_peer_name', $options['ssl']);
    }

    public function testTlsContextIncludesVerifyOptionsWhenConfigured(): void
    {
        $transport = new NativeStreamTransport(
            new ConnectionConfig('epp-test.rnids.rs'),
            new TlsConfig(
                '/tmp/client.pem',
                null,
                null,
                null,
                false,
                true,
                false,
            ),
        );

        $context = $this->invokeBuildContext($transport);
        $options = \stream_context_get_options($context);

        self::assertTrue($options['ssl']['verify_peer']);
        self::assertFalse($options['ssl']['verify_peer_name']);
    }

    /**
     * @return resource
     */
    private function invokeBuildContext(NativeStreamTransport $transport)
    {
        $method = new \ReflectionMethod($transport, 'buildContext');
        $method->setAccessible(true);

        return $method->invoke($transport);
    }
}
