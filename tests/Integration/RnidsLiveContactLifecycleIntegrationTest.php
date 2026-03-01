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

    public static function setUpBeforeClass(): void
    {
        IntegrationConfig::ensureReadyOrSkip();

        try {
            self::$client = Client::ready(IntegrationConfig::clientConfig());
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

    #[Group('contact-lifecycle')]
    public function testContactLifecycleCreateUpdateInfoDeleteFlow(): void
    {
        $factory = IntegrationConfig::contactFixtures();
        $createPayload = $factory->individualCreatePayload();
        $createdContactId = null;

        try {
            $createResult = self::client()->contact()->create($createPayload);
            $createMeta = self::client()->responseMeta();

            self::assertSame(1000, $createMeta['resultCode']);
            self::assertIsString($createResult['id']);
            self::assertNotSame('', \trim($createResult['id']));

            $createdContactId = $createResult['id'];
            $updatePayload = $factory->updatePayload($createdContactId);

            self::client()->contact()->update($updatePayload);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);

            $infoResult = self::client()->contact()->info($createdContactId);

            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame($updatePayload['email'], $infoResult['email']);
            self::assertSame($updatePayload['voice'], $infoResult['voice']);

            self::client()->contact()->delete($createdContactId);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            $createdContactId = null;
        } finally {
            if (null !== $createdContactId) {
                try {
                    self::client()->contact()->delete($createdContactId);
                } catch (\Throwable) {
                    // Best-effort cleanup for live test safety.
                }
            }
        }
    }

    #[Group('contact-domain-reassign')]
    public function testDomainAdminTechReassignmentAndResetFlow(): void
    {
        $domain = IntegrationConfig::testDomainName();
        $targetContactHandle = IntegrationConfig::testContactHandle();
        $originalAdmin = null;
        $originalTech = null;
        $adminChanged = false;
        $techChanged = false;

        try {
            try {
                self::client()->contact()->info($targetContactHandle);
            } catch (\Throwable $throwable) {
                self::markTestSkipped(
                    \sprintf(
                        'Skipping domain reassignment scenario: target contact "%s" is unavailable (%s).',
                        $targetContactHandle,
                        $throwable->getMessage(),
                    ),
                );
            }

            $initialInfo = self::client()->domain()->info($domain);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);

            $originalAdmin = $this->firstContactHandleByType($initialInfo['contacts'], 'admin');
            $originalTech = $this->firstContactHandleByType($initialInfo['contacts'], 'tech');

            if (null === $originalAdmin || null === $originalTech) {
                self::markTestSkipped(
                    \sprintf(
                        'Skipping domain reassignment scenario: domain "%s" must have both admin and tech contacts.',
                        $domain,
                    ),
                );
            }

            if ($originalAdmin === $targetContactHandle || $originalTech === $targetContactHandle) {
                self::markTestSkipped(
                    \sprintf(
                        'Skipping domain reassignment scenario: target contact "%s" is already assigned on domain "%s".',
                        $targetContactHandle,
                        $domain,
                    ),
                );
            }

            $this->reassignDomainContact($domain, 'admin', $originalAdmin, $targetContactHandle);
            $adminChanged = true;
            $afterAdminUpdate = self::client()->domain()->info($domain);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame(
                $targetContactHandle,
                $this->firstContactHandleByType($afterAdminUpdate['contacts'], 'admin'),
            );

            $this->reassignDomainContact($domain, 'admin', $targetContactHandle, $originalAdmin);
            $adminChanged = false;
            $afterAdminReset = self::client()->domain()->info($domain);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame(
                $originalAdmin,
                $this->firstContactHandleByType($afterAdminReset['contacts'], 'admin'),
            );

            $this->reassignDomainContact($domain, 'tech', $originalTech, $targetContactHandle);
            $techChanged = true;
            $afterTechUpdate = self::client()->domain()->info($domain);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame(
                $targetContactHandle,
                $this->firstContactHandleByType($afterTechUpdate['contacts'], 'tech'),
            );

            $this->reassignDomainContact($domain, 'tech', $targetContactHandle, $originalTech);
            $techChanged = false;
            $afterTechReset = self::client()->domain()->info($domain);
            self::assertSame(1000, self::client()->responseMeta()['resultCode']);
            self::assertSame(
                $originalTech,
                $this->firstContactHandleByType($afterTechReset['contacts'], 'tech'),
            );
        } finally {
            if ($adminChanged && null !== $originalAdmin) {
                try {
                    $this->reassignDomainContact($domain, 'admin', $targetContactHandle, $originalAdmin);
                } catch (\Throwable) {
                    // Best-effort cleanup for live test safety.
                }
            }

            if ($techChanged && null !== $originalTech) {
                try {
                    $this->reassignDomainContact($domain, 'tech', $targetContactHandle, $originalTech);
                } catch (\Throwable) {
                    // Best-effort cleanup for live test safety.
                }
            }
        }
    }

    private static function client(): Client
    {
        if (null === self::$client) {
            throw new \RuntimeException('Shared RNIDS integration client is not initialized.');
        }

        return self::$client;
    }

    /**
     * @param list<array{type: string, handle: string}> $contacts
     */
    private function firstContactHandleByType(array $contacts, string $type): ?string
    {
        foreach ($contacts as $contact) {
            if (($contact['type'] ?? null) !== $type) {
                continue;
            }

            $handle = $contact['handle'] ?? null;

            if (\is_string($handle) && '' !== \trim($handle)) {
                return $handle;
            }
        }

        return null;
    }

    private function reassignDomainContact(
        string $domain,
        string $type,
        string $removeHandle,
        string $addHandle,
    ): void {
        self::client()->domain()->update([
            'add' => [
                'contacts' => [
                    [ 'handle' => $addHandle, 'type' => $type ],
                ],
            ],
            'name' => $domain,
            'remove' => [
                'contacts' => [
                    [ 'handle' => $removeHandle, 'type' => $type ],
                ],
            ],
        ]);

        self::assertSame(1000, self::client()->responseMeta()['resultCode']);
    }
}
