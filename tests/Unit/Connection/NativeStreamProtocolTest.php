<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Exception\AuthenticationFailure;
use RNIDS\Exception\TransportException;
use Tests\Support\LocalEppServer;

#[Group('unit')]
final class NativeStreamProtocolTest extends TestCase
{
    public function testRealTcpSessionConsumesGreetingBeforeLoginAndCorrelatesReplies(): void
    {
        $server = $this->server('session');
        $client = new Client($this->clientConfig($server));
        try {
            $client->init();
            self::assertInstanceOf(NativeStreamTransport::class, $client->transport());
            self::assertSame('LOCAL-login', $client->responseMeta()['serverTransactionId']);
            self::assertSame(1000, $client->responseMeta()['resultCode']);

            $poll = $client->session()->poll();
            self::assertNull($poll['messageId']);
            self::assertSame(1300, $client->responseMeta()['resultCode']);
            self::assertSame('LOCAL-poll', $client->responseMeta()['serverTransactionId']);
            $client->close();
            self::assertSame(1500, $client->responseMeta()['resultCode']);
            self::assertNull($client->lastCloseError());

            $report = $server->report();
            self::assertSame(['login', 'poll', 'logout'], $report['commands']);
            self::assertCount(3, \array_unique($report['transactionIds']));
        } finally {
            $client->transport()->disconnect();
            $server->stop();
        }
    }

    public function testRejectedLoginThrowsDuringInitAndDisconnectsThePeer(): void
    {
        $server = $this->server('reject-login');
        $client = new Client($this->clientConfig($server));
        try {
            try {
                $client->init();
                self::fail('The real login response must be handled before init returns.');
            } catch (AuthenticationFailure $error) {
                self::assertSame(2200, $error->resultCode());
                self::assertSame('LOCAL-login', $error->responseMetadata()->serverTransactionId);
            }
            $report = $server->report();
            self::assertSame(['login'], $report['commands']);
            self::assertTrue($report['peerClosed']);
        } finally {
            $client->transport()->disconnect();
            $server->stop();
        }
    }

    public function testReadsAFrameWrittenInSmallFragmentsAcrossSocketReads(): void
    {
        $server = $this->server('fragmented');
        $transport = $this->transport($server);
        try {
            $transport->connect();
            self::assertSame(\str_repeat('fragmented-payload-', 4096), $transport->readFrame());
            self::assertSame('second-frame', $transport->readFrame());
        } finally {
            $transport->disconnect();
            $server->stop();
        }
    }

    public function testPeerClosingMidFrameRaisesEofAndInvalidatesTheConnection(): void
    {
        $server = $this->server('truncated');
        $transport = $this->transport($server);
        try {
            $transport->connect();
            try {
                $transport->readFrame();
                self::fail('A partial TCP frame must not be returned as a response.');
            } catch (TransportException $error) {
                self::assertStringContainsString('Unexpected EOF', $error->getMessage());
            }
            $this->expectException(TransportException::class);
            $this->expectExceptionMessage('Transport is not connected.');
            $transport->readFrame();
        } finally {
            $transport->disconnect();
            $server->stop();
        }
    }

    public function testSilentPeerRaisesConfiguredReadTimeout(): void
    {
        $server = $this->server('silent');
        $transport = $this->transport($server);
        try {
            $transport->connect();
            $this->expectException(TransportException::class);
            $this->expectExceptionMessage('Timed out while reading EPP frame.');
            $transport->readFrame();
        } finally {
            $transport->disconnect();
            $server->stop();
        }
    }

    private function server(string $scenario): LocalEppServer
    {
        try {
            return LocalEppServer::start($scenario);
        } catch (\RuntimeException $error) {
            if (\str_starts_with($error->getMessage(), 'Local TCP unavailable:')) {
                self::markTestSkipped($error->getMessage());
            }
            throw $error;
        }
    }

    /**
     * @return array{
     *   host: string, port: int, username: string, password: string,
     *   connectTimeoutSeconds: int, readTimeoutSeconds: int, allowPlaintext: bool
     * }
     */
    private function clientConfig(LocalEppServer $server): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => $server->port,
            'username' => 'LOCAL-CLIENT',
            'password' => 'local-only-password',
            'connectTimeoutSeconds' => 2,
            'readTimeoutSeconds' => 2,
            'allowPlaintext' => true,
        ];
    }

    private function transport(LocalEppServer $server): NativeStreamTransport
    {
        return new NativeStreamTransport(new ConnectionConfig('127.0.0.1', $server->port, 2, 1));
    }
}
