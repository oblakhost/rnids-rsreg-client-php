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
            $createResult = $this->client()->contact()->create($createPayload);
            $createMeta = $this->client()->responseMeta();

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
                    // Best-effort cleanup for live test safety.
                }
            }
        }
    }

    #[Group('contact-domain-reassign')]
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function testDomainAdminTechReassignmentAndResetFlow(): void
    {
        $domain = IntegrationConfig::testDomainName();
        $targetContactHandle = IntegrationConfig::testContactHandle();
        $context = null;
        $adminChanged = false;
        $techChanged = false;

        try {
            $this->ensureTargetContactExistsOrSkip($targetContactHandle);
            $context = $this->domainReassignmentContextOrSkip($domain, $targetContactHandle);

            $this->reassignDomainContact(
                $domain,
                'admin',
                $context['originalAdmin'],
                $targetContactHandle,
                $context['extensionPayload'],
            );
            $adminChanged = true;
            $this->assertDomainContactTypeHandle($domain, 'admin', $targetContactHandle);

            $this->reassignDomainContact(
                $domain,
                'admin',
                $targetContactHandle,
                $context['originalAdmin'],
                $context['extensionPayload'],
            );
            $adminChanged = false;
            $this->assertDomainContactTypeHandle($domain, 'admin', $context['originalAdmin']);

            $this->reassignDomainContact(
                $domain,
                'tech',
                $context['originalTech'],
                $targetContactHandle,
                $context['extensionPayload'],
            );
            $techChanged = true;
            $this->assertDomainContactTypeHandle($domain, 'tech', $targetContactHandle);

            $this->reassignDomainContact(
                $domain,
                'tech',
                $targetContactHandle,
                $context['originalTech'],
                $context['extensionPayload'],
            );
            $techChanged = false;
            $this->assertDomainContactTypeHandle($domain, 'tech', $context['originalTech']);
        } finally {
            if ($adminChanged && \is_array($context)) {
                try {
                    $this->reassignDomainContact(
                        $domain,
                        'admin',
                        $targetContactHandle,
                        $context['originalAdmin'],
                        $context['extensionPayload'],
                    );
                } catch (\Throwable) {
                    // Best-effort cleanup for live test safety.
                }
            }

            if ($techChanged && \is_array($context)) {
                try {
                    $this->reassignDomainContact(
                        $domain,
                        'tech',
                        $targetContactHandle,
                        $context['originalTech'],
                        $context['extensionPayload'],
                    );
                } catch (\Throwable) {
                    // Best-effort cleanup for live test safety.
                }
            }
        }
    }

    private function client(): Client
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
            $contactType = $contact['type'] ?? null;
            $handle = $contact['handle'] ?? null;

            if ($contactType === $type && \is_string($handle) && '' !== \trim($handle)) {
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
        array $extensionPayload,
    ): void {
        $this->client()->domain()->update([
            'add' => [
                'contacts' => [
                    [ 'handle' => $addHandle, 'type' => $type ],
                ],
            ],
            'extension' => $extensionPayload,
            'name' => $domain,
            'remove' => [
                'contacts' => [
                    [ 'handle' => $removeHandle, 'type' => $type ],
                ],
            ],
        ]);

        self::assertSame(1000, $this->client()->responseMeta()['resultCode']);
    }

    /**
     * @param array{
     *   extension?: array{
     *     isWhoisPrivacy?: string|null,
     *     operationMode?: string|null,
     *     notifyAdmin?: string|null,
     *     dnsSec?: string|null,
     *     remark?: string|null
     *   }
     * } $domainInfo
     *
     * @return array{
     *   remark: string,
     *   isWhoisPrivacy: bool,
     *   operationMode: string,
     *   notifyAdmin: bool,
     *   dnsSec: bool
     * }|null
     */
    private function domainUpdateExtensionPayload(array $domainInfo): ?array
    {
        $extension = $domainInfo['extension'] ?? null;

        if (!\is_array($extension)) {
            return null;
        }

        $operationMode = $this->nonEmptyStringOrNull($extension['operationMode'] ?? null);
        $remark = $this->stringOrNull($extension['remark'] ?? '');
        $isWhoisPrivacy = $this->toBool($extension['isWhoisPrivacy'] ?? null);
        $notifyAdmin = $this->toBool($extension['notifyAdmin'] ?? null);
        $dnsSec = $this->toBool($extension['dnsSec'] ?? null);

        if (null === $operationMode || null === $remark) {
            return null;
        }

        if (null === $isWhoisPrivacy || null === $notifyAdmin || null === $dnsSec) {
            return null;
        }

        return [
            'dnsSec' => $dnsSec,
            'isWhoisPrivacy' => $isWhoisPrivacy,
            'notifyAdmin' => $notifyAdmin,
            'operationMode' => $operationMode,
            'remark' => $remark,
        ];
    }

    private function toBool(mixed $value): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_string($value)) {
            return null;
        }

        return \filter_var(\trim($value), \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param non-empty-string $targetContactHandle
     */
    private function ensureTargetContactExistsOrSkip(string $targetContactHandle): void
    {
        try {
            $this->client()->contact()->info($targetContactHandle);
        } catch (\Throwable $throwable) {
            self::markTestSkipped(
                \sprintf(
                    'Skipping domain reassignment scenario: target contact "%s" is unavailable (%s).',
                    $targetContactHandle,
                    $throwable->getMessage(),
                ),
            );
        }
    }

    /**
     * @param non-empty-string $domain
     * @param non-empty-string $targetContactHandle
     *
     * @return array{
     *   originalAdmin: non-empty-string,
     *   originalTech: non-empty-string,
     *   extensionPayload: array{
     *     remark: string,
     *     isWhoisPrivacy: bool,
     *     operationMode: string,
     *     notifyAdmin: bool,
     *     dnsSec: bool
     *   }
     * }
     */
    private function domainReassignmentContextOrSkip(string $domain, string $targetContactHandle): array
    {
        $initialInfo = $this->client()->domain()->info($domain);
        self::assertSame(1000, $this->client()->responseMeta()['resultCode']);

        $originalAdmin = $this->firstContactHandleByType($initialInfo['contacts'], 'admin');
        $originalTech = $this->firstContactHandleByType($initialInfo['contacts'], 'tech');
        $extensionPayload = $this->domainUpdateExtensionPayload($initialInfo);

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

        if (null === $extensionPayload) {
            self::markTestSkipped(
                \sprintf(
                    'Skipping domain reassignment scenario: unable to resolve required domain extension values'
                    . ' for "%s".',
                    $domain,
                ),
            );
        }

        return [
            'extensionPayload' => $extensionPayload,
            'originalAdmin' => $originalAdmin,
            'originalTech' => $originalTech,
        ];
    }

    /**
     * @param non-empty-string $domain
     * @param 'admin'|'tech' $type
     * @param non-empty-string $expectedHandle
     */
    private function assertDomainContactTypeHandle(string $domain, string $type, string $expectedHandle): void
    {
        $domainInfo = $this->client()->domain()->info($domain);

        self::assertSame(1000, $this->client()->responseMeta()['resultCode']);
        self::assertSame(
            $expectedHandle,
            $this->firstContactHandleByType($domainInfo['contacts'], $type),
        );
    }

    private function nonEmptyStringOrNull(mixed $value): ?string
    {
        if (!\is_string($value) || '' === \trim($value)) {
            return null;
        }

        return $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        return $value;
    }
}
