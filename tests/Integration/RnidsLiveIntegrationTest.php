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
        IntegrationConfig::ensureReadyOrSkip();

        try {
            self::$client = new Client(IntegrationConfig::clientConfig());
        } catch (\Throwable $throwable) {
            throw new \PHPUnit\Framework\SkippedTestSuiteError(
                \sprintf('Unable to initialize RNIDS live client: %s', $throwable->getMessage()),
                (int) $throwable->getCode(),
                $throwable,
            );
        }
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

        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertIsArray($result['greeting']['objectUris']);
        self::assertNotEmpty($result['greeting']['objectUris']);
        self::assertContains('urn:ietf:params:xml:ns:domain-1.0', $result['greeting']['objectUris']);
    }

    /**
     * @return non-empty-string
     */
    public function testDomainRegisterCreatesUniqueDomain(): string
    {
        IntegrationConfig::ensureRegisterReadyOrSkip();

        $domain = IntegrationConfig::uniqueRegisterDomainName();
        $registerRequest = IntegrationConfig::domainRegisterRequest($domain);
        $result = self::client()->domain()->register($registerRequest);

        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame($domain, $result['creation']['name']);
        self::assertNotNull($result['creation']['createDate']);
        self::assertNotNull($result['creation']['expirationDate']);

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

        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertCount(1, $result['items']);
        self::assertSame($domain, $result['items'][0]['name']);
        self::assertIsBool($result['items'][0]['available']);
    }

    /**
     * @param non-empty-string $registeredDomain
     */
    #[Depends('testDomainRegisterCreatesUniqueDomain')]
    public function testDomainInfoReadsRegisteredDomain(string $registeredDomain): void
    {
        $domain = $registeredDomain;
        $result = self::client()->domain()->info($domain);

        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame($domain, $result['info']['name']);
        self::assertSame(IntegrationConfig::registerRegistrantHandle(), $result['info']['registrant']);
        self::assertIsArray($result['info']['statuses']);
        self::assertArrayHasKey('extension', $result['info']);
    }

    public function testDomainInfoReadsConfiguredStableFixtureDomain(): void
    {
        $domain = IntegrationConfig::testDomainName();
        $result = self::client()->domain()->info($domain);

        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame($domain, $result['info']['name']);
        self::assertIsArray($result['info']['statuses']);
    }

    public function testPollReqReturnsMetadataAndQueueShape(): void
    {
        $result = self::client()->session()->poll();

        self::assertIsInt($result['metadata']['resultCode']);
        self::assertArrayHasKey('queue', $result);
        self::assertArrayHasKey('count', $result['queue']);
        self::assertArrayHasKey('messageId', $result['queue']);
    }
}
