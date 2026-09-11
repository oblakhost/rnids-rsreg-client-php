<?php

declare(strict_types=1);

namespace Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Exception\AuthenticationFailure;
use RNIDS\Exception\MalformedResponseException;
use RNIDS\Exception\TransportException;
use Tests\Support\SessionPeerTransport;

final class ClientSessionProtocolTest extends TestCase
{
    public function testFailedReconnectClearsThePreviousSessionMetadata(): void
    {
        $peer = new SessionPeerTransport();
        $client = $this->client($peer);
        $client->init();
        $client->close();
        $peer->failConnect = true;

        try {
            $client->init();
            self::fail('Failed connection was accepted.');
        } catch (TransportException) {
            self::assertNull($client->responseMeta());
        }
    }

    public function testInitReadsActualLoginResultAfterConnectionGreeting(): void
    {
        $peer = new SessionPeerTransport();
        $client = $this->client($peer);
        $client->init();

        self::assertSame('Test peer result', $client->responseMeta()['message']);
        self::assertNotNull($client->responseMeta()['clientTransactionId']);
        self::assertCount(1, $peer->requests);
        self::assertStringContainsString('<login>', $peer->requests[0]);
        self::assertStringContainsString('urn:ietf:params:xml:ns:secDNS-1.1', $peer->requests[0]);
        $client->close();
    }

    public function testInitSurfacesRejectedLoginBeforeReturning(): void
    {
        $peer = new SessionPeerTransport(2200);
        $client = $this->client($peer);

        try {
            $client->init();
            self::fail('Rejected login was accepted.');
        } catch (AuthenticationFailure $exception) {
            self::assertSame(2200, $exception->resultCode());
            self::assertFalse($peer->connected);
        }
    }

    public function testExplicitLogoutAllowsReinitializationWithoutDuplicateLogout(): void
    {
        $peer = new SessionPeerTransport();
        $client = $this->client($peer);
        $client->init();
        $client->session()->logout();
        self::assertFalse($peer->connected);
        $client->close();
        self::assertCount(2, $peer->requests);
        $client->init();
        self::assertTrue($peer->connected);
        self::assertCount(3, $peer->requests);
        $client->close();
    }

    public function testMismatchedResponseDisconnectsAndClearsPreviousMetadata(): void
    {
        $peer = new SessionPeerTransport();
        $client = $this->client($peer);
        $client->init();
        $peer->nextResponse = SessionPeerTransport::response(1300, 'OTHER-COMMAND');

        try {
            $client->session()->poll();
            self::fail('Response for another command was accepted.');
        } catch (\RNIDS\Exception\MalformedResponseException $exception) {
            self::assertStringContainsString('transaction', $exception->getMessage());
            self::assertFalse($peer->connected);
            self::assertNull($client->responseMeta());
        }
    }

    public function testTransportFailureInvalidatesCachedServiceAndAllowsReconnect(): void
    {
        $peer = new SessionPeerTransport();
        $client = $this->client($peer);
        $client->init();
        $session = $client->session();
        $peer->failRead = true;

        try {
            $session->poll();
            self::fail('Timeout was accepted.');
        } catch (\RNIDS\Exception\TransportException) {
            self::assertNull($client->responseMeta());
            self::assertFalse($peer->connected);
        }

        $count = \count($peer->requests);
        try {
            $session->poll();
            self::fail('Cached service used a disconnected session.');
        } catch (\RNIDS\Exception\TransportException) {
            self::assertCount($count, $peer->requests);
        }

        $peer->failRead = false;
        $client->init();
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        $client->close();
    }

    public function testHelloGreetingModeAuthenticatesARegistryThatWaitsForHello(): void
    {
        $peer = new SessionPeerTransport(1000, false);
        $client = new Client([
            'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret',
            'greetingMode' => 'hello',
        ], $peer);
        $client->init();
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        self::assertCount(2, $peer->requests);
        self::assertStringContainsString('<hello/>', $peer->requests[0]);
        self::assertStringContainsString('<login>', $peer->requests[1]);
        $client->session()->poll();
        self::assertSame(1300, $client->responseMeta()['resultCode']);
        $client->close();
        self::assertFalse($peer->connected);
    }

    public function testExplicitMissingTransactionIdModeAcceptsRnidsLoginAndPollResponses(): void
    {
        $peer = new SessionPeerTransport(1000, false);
        $peer->omitTransactionIds = true;
        $client = new Client([
            'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret',
            'greetingMode' => 'hello', 'requireClientTransactionId' => false,
        ], $peer);
        $client->init();
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        self::assertNull($client->responseMeta()['clientTransactionId']);
        $client->session()->poll();
        self::assertSame(1300, $client->responseMeta()['resultCode']);
        $peer->nextResponse = SessionPeerTransport::response(1300, 'ANOTHER-COMMAND');
        try {
            $client->session()->poll();
            self::fail('Present mismatched transaction ID was accepted.');
        } catch (MalformedResponseException) {
            self::assertFalse($peer->connected);
            self::assertNull($client->responseMeta());
        }
    }

    public function testDefaultModeStillRejectsMissingTransactionIds(): void
    {
        $peer = new SessionPeerTransport();
        $peer->omitTransactionIds = true;
        $client = $this->client($peer);
        $this->expectException(MalformedResponseException::class);
        $client->init();
    }

    public function testMissingTransactionIdCompatibilityCannotAuthenticateWithAnExtraGreeting(): void
    {
        $peer = new SessionPeerTransport(2200, true);
        $client = new Client([
            'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret',
            'greetingMode' => 'hello', 'requireClientTransactionId' => false,
        ], $peer);
        try {
            $client->init();
            self::fail('Greeting was accepted in place of login rejection.');
        } catch (MalformedResponseException) {
            self::assertFalse($peer->connected);
            self::assertNull($client->responseMeta());
        }
    }

    public function testMissingTransactionIdCompatibilityRejectsGreetingsInCommandResponses(): void
    {
        $peer = new SessionPeerTransport();
        $client = new Client([
            'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret',
            'requireClientTransactionId' => false,
        ], $peer);
        $client->init();
        $peer->nextResponse = SessionPeerTransport::greeting();
        try {
            $client->session()->poll();
            self::fail('Greeting was accepted as a poll response.');
        } catch (MalformedResponseException) {
            self::assertFalse($peer->connected);
            self::assertNull($client->responseMeta());
        }
    }

    private function client(SessionPeerTransport $peer): Client
    {
        return new Client(
            [ 'host' => 'unused.invalid', 'username' => 'audit', 'password' => 'secret' ],
            $peer,
        );
    }
}
