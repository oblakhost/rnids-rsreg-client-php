<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Exception\ObjectAssociationConflict;
use RNIDS\Exception\ObjectMissing;
use Tests\Integration\Support\IntegrationConfig;
use Tests\Integration\Support\LiveCleanup;

#[Group('integration')]
#[Group('live')]
#[Group('dnssec')]
final class RnidsLiveDnssecIntegrationTest extends TestCase
{
    private ?Client $client = null;

    public function testDnssecRecordsRoundTripThroughoutOwnedDomainLifecycle(): void
    {
        $client = $this->client();
        $cleanup = new LiveCleanup();
        $pendingDeletion = false;
        $domain = IntegrationConfig::uniqueRegisterDomainName();
        $first = $this->dsRecord($domain);
        $second = $this->dsRecord($domain);
        $replacement = $this->dsRecord($domain);

        try {
            $contact = $this->createContact($cleanup, $pendingDeletion);
            self::assertTrue($client->domain()->check($domain)[0]['available']);
            $request = IntegrationConfig::domainRegisterRequest($domain);
            $request['registrant'] = $contact;
            $request['contacts'] = [
                ['type' => 'admin', 'handle' => $contact],
                ['type' => 'tech', 'handle' => $contact],
            ];
            $request['dnssec'] = [ 'records' => [ $first ] ];
            $cleanup->add('domain ' . $domain, function () use ($domain, &$pendingDeletion): ?string {
                return $this->removeDomain($domain, $pendingDeletion);
            });
            $client->domain()->register($request);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $this->assertDomainRecords($domain, [ $first ]);

            $client->domain()->update([ 'name' => $domain, 'dnssec' => [ 'add' => [ $second ] ] ]);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $this->assertDomainRecords($domain, [ $first, $second ]);

            $client->domain()->update([ 'name' => $domain, 'dnssec' => [ 'remove' => [ $first ] ] ]);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $this->assertDomainRecords($domain, [ $second ]);

            $client->domain()->update([
                'dnssec' => ['removeAll' => true, 'add' => [$replacement]],
                'name' => $domain,
            ]);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $this->assertDomainRecords($domain, [ $replacement ]);

            $client->domain()->update([ 'name' => $domain, 'dnssec' => [ 'removeAll' => true ] ]);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $this->assertDomainRecords($domain, []);
        } finally {
            $cleanup->run();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $failure = IntegrationConfig::liveReadinessFailureReason();
        if (null !== $failure) {
            self::markTestSkipped($failure);
        }

        $this->client = Client::ready(IntegrationConfig::clientConfig());
    }

    protected function tearDown(): void
    {
        try {
            $this->client?->close();
        } finally {
            $this->client = null;
            parent::tearDown();
        }
    }

    /** @return non-empty-string */
    private function createContact(LiveCleanup $cleanup, bool &$pendingDeletion): string
    {
        $client = $this->client();
        $payload = IntegrationConfig::contactFixtures()->withRunToken(\bin2hex(\random_bytes(5)))
            ->individualCreatePayload();
        $payload['id'] = 'OBL-' . $payload['id'];
        $contact = $payload['id'];
        self::assertTrue($client->contact()->check($contact)[0]['available']);
        // Retain the intended identifier even if the registry's create response is lost.
        $cleanup->add('contact ' . $contact, function () use ($contact, &$pendingDeletion): ?string {
            return $this->removeContact($contact, $pendingDeletion);
        });
        $created = $client->contact()->create($payload);
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        self::assertSame($contact, $created['id']);

        return $contact;
    }

    private function removeDomain(string $domain, bool &$pendingDeletion): ?string
    {
        $client = $this->client();
        try {
            $client->domain()->delete($domain);
            self::assertSame(1000, $client->responseMeta()['resultCode']);
            $info = $client->domain()->info($domain);
        } catch (ObjectMissing) {
            return null;
        }

        self::assertContains('pendingDelete', $info['statuses']);
        $pendingDeletion = true;

        return 'pending';
    }

    private function removeContact(string $contact, bool $pendingDeletion): ?string
    {
        $client = $this->client();
        try {
            $client->contact()->delete($contact);
        } catch (ObjectMissing) {
            return null;
        } catch (ObjectAssociationConflict $exception) {
            if (!$pendingDeletion) {
                throw $exception;
            }
            self::assertContains('linked', $client->contact()->info($contact)['statuses']);

            return 'pending';
        }
        self::assertSame(1000, $client->responseMeta()['resultCode']);
        try {
            $client->contact()->info($contact);
            self::fail('Deleted DNSSEC fixture contact is still present in the registry.');
        } catch (ObjectMissing $exception) {
            self::assertSame(2303, $exception->resultCode());
        }

        return null;
    }

    /**
     * @param list<array{alg: int, digest: string, digestType: int, keyTag: int}> $expected
     */
    private function assertDomainRecords(string $domain, array $expected): void
    {
        $actual = $this->client()->domain()->info($domain)['dnssec']['records'];
        self::assertSame(1000, $this->client()->responseMeta()['resultCode']);
        self::assertSame($this->canonicalRecords($expected), $this->canonicalRecords($actual));
    }

    /**
     * @param list<array{alg: int, digest: string, digestType: int, keyTag: int}> $records
     * @return list<array{alg: int, digest: string, digestType: int, keyTag: int}>
     */
    private function canonicalRecords(array $records): array
    {
        foreach ($records as &$record) {
            $record['digest'] = \strtoupper($record['digest']);
        }
        unset($record);
        \usort($records, static fn(array $left, array $right): int => $left['digest'] <=> $right['digest']);

        return $records;
    }

    /** @return array{alg: 13, digest: non-empty-string, digestType: 2, keyTag: int<0, 65535>} */
    private function dsRecord(string $domain): array
    {
        // Derive DS data from a fresh P-256 DNSKEY; no private key is persisted.
        $rdata = $this->dnskeyRdata();
        $keyTag = 0;
        for ($index = 0; $index < \strlen($rdata); $index++) {
            $keyTag += $index & 1 ? \ord($rdata[$index]) : \ord($rdata[$index]) << 8;
        }
        $keyTag += ($keyTag >> 16) & 0xffff;
        $owner = '';
        foreach (\explode('.', \strtolower($domain)) as $label) {
            $owner .= \chr(\strlen($label)) . $label;
        }

        return [
            'alg' => 13,
            'digest' => \strtoupper(\hash('sha256', $owner . "\0" . $rdata)),
            'digestType' => 2,
            'keyTag' => $keyTag & 0xffff,
        ];
    }

    private function dnskeyRdata(): string
    {
        $key = \openssl_pkey_new([ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ]);
        $details = false === $key ? false : \openssl_pkey_get_details($key);
        if (false === $details || !isset($details['ec']['x'], $details['ec']['y'])) {
            throw new \RuntimeException('Could not generate a DNSSEC fixture key.');
        }
        $publicKey = \str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
            . \str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        return \pack('nCC', 257, 3, 13) . $publicKey;
    }

    private function client(): Client
    {
        if (null === $this->client) {
            throw new \RuntimeException('DNSSEC integration client is not initialized.');
        }

        return $this->client;
    }
}
