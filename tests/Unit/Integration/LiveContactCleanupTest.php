<?php

declare(strict_types=1);

namespace Tests\Unit\Integration;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Connection\Transport;
use Tests\Integration\RnidsLiveContactLifecycleIntegrationTest;
use Tests\Support\SessionPeerTransport;

final class LiveContactCleanupTest extends TestCase
{
    public function testMissingCreateResponseIdStillCleansUpTheActualRequestedContact(): void
    {
        $peer = new class () implements Transport {
            /** @var list<string> */
            public array $requests = [];
            private string $response = '';

            public function connect(): void
            {
                $this->response = SessionPeerTransport::greeting();
            }

            public function disconnect(): void
            {
                // No external connection exists in this local peer.
            }

            public function writeFrame(string $payload): void
            {
                $this->requests[] = $payload;
                \preg_match('~<clTRID>([^<]+)</clTRID>~', $payload, $matches);
                $this->response = SessionPeerTransport::response(1000, $matches[1]);
            }

            public function readFrame(): string
            {
                return $this->response;
            }
        };
        $client = Client::ready(
            [ 'host' => 'unused.invalid', 'username' => 'local', 'password' => 'local' ],
            $peer,
        );
        $clientProperty = new \ReflectionProperty(RnidsLiveContactLifecycleIntegrationTest::class, 'client');
        $previousClient = $clientProperty->getValue();
        $clientProperty->setValue(null, $client);
        $scenario = new RnidsLiveContactLifecycleIntegrationTest(
            'testContactLifecycleCreateUpdateInfoDeleteFlow',
        );

        try {
            try {
                $scenario->testContactLifecycleCreateUpdateInfoDeleteFlow();
                self::fail('A response without a contact ID must fail the live scenario.');
            } catch (\PHPUnit\Framework\ExpectationFailedException) {
                self::assertCount(3, $peer->requests);
                \preg_match('~<contact:id>([^<]+)</contact:id>~', $peer->requests[1], $createId);
                \preg_match('~<contact:id>([^<]+)</contact:id>~', $peer->requests[2], $deleteId);
                self::assertSame($createId[1], $deleteId[1]);
            }
        } finally {
            $clientProperty->setValue(null, $previousClient);
            $client->close();
        }
    }
}
