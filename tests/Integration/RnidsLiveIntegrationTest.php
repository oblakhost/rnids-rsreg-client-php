<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use Tests\Integration\Support\IntegrationConfig;
use Tests\Integration\Support\LiveCleanup;

#[Group('integration')]
#[Group('live')]
final class RnidsLiveIntegrationTest extends TestCase
{
    private static ?Client $client = null;
    private static ?string $readinessFailure = null;

    public static function setUpBeforeClass(): void
    {
        self::$readinessFailure = IntegrationConfig::liveReadinessFailureReason();

        if (null !== self::$readinessFailure) {
            return;
        }

        self::$client = Client::ready(IntegrationConfig::clientConfig());
    }

    public static function tearDownAfterClass(): void
    {
        self::$client?->close();
        self::$client = null;

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        if (null !== self::$readinessFailure) {
            self::markTestSkipped(self::$readinessFailure);
        }

        parent::setUp();
    }

    private static function client(): Client
    {
        if (null === self::$client) {
            throw new \RuntimeException('Shared RNIDS integration client is not initialized.');
        }

        return self::$client;
    }

    public function testHelloReturnsServerGreeting(): void
    {
        $result = self::client()->session()->hello();

        self::assertIsArray($result['objectUris']);
        self::assertNotEmpty($result['objectUris']);
        self::assertContains('urn:ietf:params:xml:ns:domain-1.0', $result['objectUris']);
    }

    public function testDomainLifecycleRegistersUpdatesRenewsAndDeletesOwnedResources(): void
    {
        $address = \getenv('RNIDS_EPP_TEST_HOST_IPV4');
        self::assertNotFalse($address, 'Set RNIDS_EPP_TEST_HOST_IPV4 to an allowed test nameserver address.');
        self::assertNotFalse(\filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        $client = self::client();
        $cleanup = new LiveCleanup();
        try {
            $originalContact = $this->createContact($cleanup);
            $replacementContact = $this->createContact($cleanup);
            $domain = IntegrationConfig::uniqueRegisterDomainName();
            $request = IntegrationConfig::domainRegisterRequest($domain);
            $request['registrant'] = $originalContact;
            $request['contacts'] = [
                ['handle' => $originalContact, 'type' => 'admin'],
                ['handle' => $originalContact, 'type' => 'tech'],
            ];
            $result = $client->domain()->register($request);
            $cleanup->add('domain ' . $domain, static function () use ($client, $domain): void {
                $client->domain()->delete($domain);
                self::assertSame(1000, $client->responseMeta()['resultCode']);
            });
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            self::assertSame($domain, $result['name']);
            self::assertInstanceOf(\DateTimeImmutable::class, $result['expirationDate']);
            self::assertFalse($client->domain()->check($domain)[0]['available']);
            $info = $client->domain()->info($domain);
            self::assertSame($originalContact, $info['registrant']);

            $client->domain()->update([
                'name' => $domain,
                'add' => ['contacts' => [['type' => 'tech', 'handle' => $replacementContact]]],
                'remove' => ['contacts' => [['type' => 'tech', 'handle' => $originalContact]]],
            ]);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            self::assertSame($replacementContact, $client->domain()->info($domain)['techContact']);

            $renewed = $client->domain()->renew($domain, 1, $result['expirationDate']);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            self::assertGreaterThan($result['expirationDate'], $renewed['expiryDate']);
            $this->exerciseHostLifecycle($domain);
        } finally {
            $cleanup->run();
        }
    }

    private function createContact(LiveCleanup $cleanup): string
    {
        $client = self::client();
        $payload = IntegrationConfig::contactFixtures()->withRunToken(\bin2hex(\random_bytes(4)))
            ->individualCreatePayload();
        $payload['id'] = 'OBL-' . $payload['id'];
        $result = $client->contact()->create($payload);
        $id = $result['id'] ?? $payload['id'];
        $cleanup->add('contact ' . $id, static function () use ($client, $id): void {
            $client->contact()->delete($id);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
        });
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        self::assertSame($payload['id'], $result['id']);
        return $id;
    }

    private function exerciseHostLifecycle(string $domain): void
    {
        $address = \getenv('RNIDS_EPP_TEST_HOST_IPV4');
        self::assertNotFalse($address, 'Set RNIDS_EPP_TEST_HOST_IPV4 to an allowed test nameserver address.');
        self::assertNotFalse(\filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        $host = 'ns1.' . $domain;
        $createdHost = null;
        try {
            $result = self::client()->host()->create($host, $address);
            $createdHost = $host;
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame($host, $result['name']);
            self::assertFalse(self::client()->host()->check($host)[0]['available']);
            self::assertContains($address, self::client()->host()->info($host)['ipv4']);
            $newName = 'ns2.' . $domain;
            self::client()->host()->update(['name' => $host, 'newName' => $newName]);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            $createdHost = $newName;
            self::assertSame($newName, self::client()->host()->info($newName)['name']);
        } finally {
            if (null !== $createdHost) {
                $cleanup = new LiveCleanup();
                $cleanup->add('host ' . $createdHost, static function () use ($createdHost): void {
                    self::client()->host()->delete($createdHost);
                    self::assertSame(1000, self::client()->responseMeta()['resultCode']);
                });
                $cleanup->run();
            }
        }
    }

    public function testDomainInfoReadsConfiguredStableFixtureDomain(): void
    {
        $domain = \getenv('RNIDS_EPP_TEST_DOMAIN');
        if (!\is_string($domain) || '' === \trim($domain)) {
            self::markTestSkipped('RNIDS_EPP_TEST_DOMAIN is required for the stable fixture info test.');
        }
        $result = self::client()->domain()->info($domain);

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
        self::assertSame($domain, $result['name']);
        self::assertIsArray($result['statuses']);
    }

    public function testPollReqReturnsMetadataAndQueueShape(): void
    {
        $result = self::client()->session()->poll();

        self::assertIsInt(self::client()->responseMeta()['resultCode']);
        self::assertArrayHasKey('count', $result);
        self::assertArrayHasKey('messageId', $result);
    }

    public function testPollAcknowledgesOnlyExplicitlyApprovedMessage(): void
    {
        $messageId = \getenv('RNIDS_EPP_POLL_ACK_MESSAGE_ID');
        if (!\is_string($messageId) || '' === \trim($messageId)) {
            self::markTestSkipped('Set RNIDS_EPP_POLL_ACK_MESSAGE_ID to an explicitly approved test queue message.');
        }
        $result = self::client()->session()->poll();
        self::assertSame($messageId, $result['messageId'], 'Refusing to acknowledge a different queue message.');
        self::client()->session()->poll(['operation' => 'ack', 'messageId' => $messageId]);
        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
    }
}
