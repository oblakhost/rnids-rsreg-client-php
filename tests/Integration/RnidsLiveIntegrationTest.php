<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use Tests\Integration\Support\IntegrationConfig;

#[Group('integration')]
#[Group('live')]
final class RnidsLiveIntegrationTest extends TestCase
{
    private static ?Client $client = null;

    public static function setUpBeforeClass(): void
    {
        IntegrationConfig::ensureReadyOrFail();
        self::$client = Client::ready(IntegrationConfig::clientConfig());
    }

    public static function tearDownAfterClass(): void
    {
        self::$client?->close();
        self::$client = null;

        parent::tearDownAfterClass();
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

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
        self::assertIsArray($result['objectUris']);
        self::assertNotEmpty($result['objectUris']);
        self::assertContains('urn:ietf:params:xml:ns:domain-1.0', $result['objectUris']);
    }

    /**
     * @return non-empty-string
     */
    public function testDomainRegisterCreatesUniqueDomain(): string
    {
        IntegrationConfig::ensureRegisterReadyOrFail();

        $domain = IntegrationConfig::uniqueRegisterDomainName();
        $registerRequest = IntegrationConfig::domainRegisterRequest($domain);
        $result = self::client()->domain()->register($registerRequest);

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
        self::assertSame($domain, $result['name']);
        self::assertNotNull($result['createDate']);
        self::assertNotNull($result['expirationDate']);

        return $domain;
    }

    /**
     * @param non-empty-string $registeredDomain
     */
    #[Depends('testDomainRegisterCreatesUniqueDomain')]
    public function testDomainCheckReturnsItemForRegisteredDomain(string $registeredDomain): void
    {
        $domain = $registeredDomain;
        $result = self::client()->domain()->check([ 'names' => [ $domain ] ]);

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
        self::assertCount(1, $result);
        self::assertSame($domain, $result[0]['name']);
        self::assertIsBool($result[0]['available']);
    }

    /**
     * @param non-empty-string $registeredDomain
     */
    #[Depends('testDomainRegisterCreatesUniqueDomain')]
    public function testDomainInfoReadsRegisteredDomain(string $registeredDomain): void
    {
        $domain = $registeredDomain;
        $result = self::client()->domain()->info($domain);

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
        self::assertSame($domain, $result['name']);
        self::assertSame(IntegrationConfig::registerRegistrantHandle(), $result['registrant']);
        self::assertIsArray($result['statuses']);
        self::assertArrayHasKey('extension', $result);
    }

    public function testDomainInfoReadsConfiguredStableFixtureDomain(): void
    {
        $domain = IntegrationConfig::testDomainName();
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
}
