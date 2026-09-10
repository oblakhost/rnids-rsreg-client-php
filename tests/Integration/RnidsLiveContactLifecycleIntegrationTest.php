<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use Tests\Integration\Support\IntegrationConfig;

#[Group('integration')]
#[Group('live')]
#[Group('contact')]
final class RnidsLiveContactLifecycleIntegrationTest extends TestCase
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

    #[Group('contact-lifecycle')]
    public function testContactLifecycleCreateUpdateInfoDeleteFlow(): void
    {
        $factory = IntegrationConfig::contactFixtures()->withRunToken(
            \strtoupper(\bin2hex(\random_bytes(4))),
        );
        $createPayload = $factory->individualCreatePayload();
        $createPayload['id'] = 'OBL-' . $createPayload['id'];
        $createdContactId = null;

        try {
            $createResult = $this->client()->contact()->create($createPayload);
            $createMeta = $this->client()->responseMeta();

            $createdContactId = $createResult['id'] ?? $createPayload['id'];
            self::assertSame(1000, $createMeta['resultCode']);
            self::assertIsString($createResult['id']);
            self::assertNotSame('', \trim($createResult['id']));

            $createdContactId = $createResult['id'];
            $updatePayload = $factory->updatePayload($createdContactId);

            $this->client()->contact()->update($updatePayload);
            self::assertSame(1000, $this->client()->responseMeta()['resultCode']);

            $infoResult = $this->client()->contact()->info($createdContactId);

            self::assertSame(1000, $this->client()->responseMeta()['resultCode']);
            self::assertSame($updatePayload['email'], $infoResult['email']);
            self::assertSame($updatePayload['voice'], $infoResult['voice']);

            $this->client()->contact()->delete($createdContactId);
            self::assertSame(1000, $this->client()->responseMeta()['resultCode']);
            $createdContactId = null;
        } finally {
            if (null !== $createdContactId) {
                try {
                    $this->client()->contact()->delete($createdContactId);
                } catch (\Throwable) {
                    self::fail(
                        'Live cleanup failed for contact ' . $createdContactId . '. Remove it manually.',
                    );
                }
            }
        }
    }

    protected function setUp(): void
    {
        if (null !== self::$readinessFailure) {
            self::markTestSkipped(self::$readinessFailure);
        }

        parent::setUp();
    }

    private function client(): Client
    {
        if (null === self::$client) {
            throw new \RuntimeException('Shared RNIDS integration client is not initialized.');
        }

        return self::$client;
    }
}
