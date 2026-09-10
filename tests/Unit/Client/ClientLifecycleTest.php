<?php

declare(strict_types=1);

namespace Tests\Unit\Client;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Exception\TransportException;
use Tests\Support\SessionPeerTransport;

#[Group('unit')]
final class ClientLifecycleTest extends TestCase
{
    public function testConstructorDoesNotConnectWhenTlsConfigIsInvalid(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());
        self::assertInstanceOf(Client::class, $client);
        $client->close();
    }

    public function testDomainServiceAccessBeforeInitThrowsClearError(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Client is not initialized. Call init() first or use Client::ready().');
        $client->domain();
    }

    public function testInitBootstrapsOnceAndCloseLogsOutAndDisconnects(): void
    {
        $peer = new SessionPeerTransport();
        $client = new Client($this->baseConfig(), $peer);
        $client->init();
        $client->init();
        self::assertCount(1, $peer->requests);
        self::assertStringContainsString('<login>', $peer->requests[0]);
        $client->close();
        self::assertFalse($peer->connected);
        self::assertCount(2, $peer->requests);
        self::assertStringContainsString('<logout/>', $peer->requests[1]);
    }

    public function testReadyAttemptsInitializationFlow(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TLS client certificate file is not readable');
        Client::ready($this->configWithInvalidTlsClientCertificatePath());
    }

    public function testCloseBeforeInitIsIdempotentNoOp(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());
        $client->close();
        $client->close();
        self::assertNull($client->responseMeta());
    }

    public function testCloseThrowsAndRecordsLogoutFailure(): void
    {
        $peer = new SessionPeerTransport();
        $client = Client::ready($this->baseConfig(), $peer);
        $peer->failRead = true;
        try {
            $client->close();
            self::fail('Expected close to report the logout timeout.');
        } catch (\RNIDS\Exception\TransportException $exception) {
            self::assertSame($exception, $client->lastCloseError());
            self::assertFalse($peer->connected);
        }
        $client->close();
        self::assertCount(2, $peer->requests);
    }

    public function testCloseThrowsAndRecordsDisconnectFailure(): void
    {
        $peer = new SessionPeerTransport();
        $client = Client::ready($this->baseConfig(), $peer);
        $peer->failDisconnect = true;
        try {
            $client->close();
            self::fail('Expected close to report the disconnect failure.');
        } catch (\RNIDS\Exception\TransportException $exception) {
            self::assertSame($exception, $client->lastCloseError());
            self::assertStringContainsString('Disconnect failed', $exception->getMessage());
        }
    }

    public function testDestructorSuppressesShutdownExceptionsAndRecordsError(): void
    {
        $peer = new SessionPeerTransport();
        $client = Client::ready($this->baseConfig(), $peer);
        $peer->failRead = true;
        $client->__destruct();
        self::assertInstanceOf(\RNIDS\Exception\TransportException::class, $client->lastCloseError());
        self::assertFalse($peer->connected);
    }

    /** @return array{host: non-empty-string, username: non-empty-string, password: non-empty-string} */
    private function baseConfig(): array
    {
        return [ 'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret' ];
    }

    /**
     * @return array{
     *   host: non-empty-string, username: non-empty-string, password: non-empty-string,
     *   tls: array{clientCertificatePath: non-empty-string}
     * }
     */
    private function configWithInvalidTlsClientCertificatePath(): array
    {
        return $this->baseConfig() + [ 'tls' => [ 'clientCertificatePath' => '/missing/client.pem' ] ];
    }
}
