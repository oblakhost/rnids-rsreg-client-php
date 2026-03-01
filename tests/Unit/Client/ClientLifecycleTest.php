<?php

declare(strict_types=1);

namespace Tests\Unit\Client;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Connection\Transport;
use RNIDS\Session\SessionService;

#[Group('unit')]
final class ClientLifecycleTest extends TestCase
{
    public function testConstructorDoesNotConnectWhenTlsConfigIsInvalid(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());

        self::assertInstanceOf(Client::class, $client);

        $client->close();
    }

    public function testDomainServiceAccessBeforeInitThrowsClearError(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Client is not initialized. Call init() first or use Client::ready().');

        $client->domain();
    }

    public function testInitBootstrapsOnceAndCloseLogsOutAndDisconnects(): void
    {
        $client = new Client($this->baseConfig());
        $transport = new class () implements Transport {
            public int $connectCalls = 0;

            public int $disconnectCalls = 0;

            /** @var list<string> */
            public array $writtenPayloads = [];

            /** @var list<string> */
            private array $responses;

            public function __construct()
            {
                $this->responses = [
                    '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<greeting>'
                    . '<svID>RNIDS EPP</svID>'
                    . '<svDate>2026-03-01T00:00:00.0Z</svDate>'
                    . '<svcMenu><version>1.0</version><lang>en</lang></svcMenu>'
                    . '</greeting>'
                    . '</epp>',
                    '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1000"><msg>Command completed successfully</msg></result>'
                    . '<trID><clTRID>SESSION-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>',
                    '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1500"><msg>Command completed successfully; ending session</msg></result>'
                    . '<trID><clTRID>SESSION-00000002</clTRID><svTRID>SV-2</svTRID></trID>'
                    . '</response>'
                    . '</epp>',
                ];
            }

            public function connect(): void
            {
                ++$this->connectCalls;
            }

            public function disconnect(): void
            {
                ++$this->disconnectCalls;
            }

            public function writeFrame(string $payload): void
            {
                $this->writtenPayloads[] = $payload;
            }

            public function readFrame(): string
            {
                if ([] === $this->responses) {
                    throw new \RuntimeException('No mocked responses left in transport.');
                }

                return \array_shift($this->responses) ?? '';
            }
        };

        $this->setClientTransportAndSession($client, $transport);

        $client->init();
        $client->init();

        self::assertSame(1, $transport->connectCalls);
        self::assertCount(2, $transport->writtenPayloads);
        self::assertStringContainsString('<hello/>', $transport->writtenPayloads[0]);
        self::assertStringContainsString('<login>', $transport->writtenPayloads[1]);

        $client->close();
        self::assertSame(1, $transport->disconnectCalls);
        self::assertCount(3, $transport->writtenPayloads);
        self::assertStringContainsString('<logout/>', $transport->writtenPayloads[2]);
    }

    public function testReadyAttemptsInitializationFlow(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TLS client certificate file is not readable');

        Client::ready($this->configWithInvalidTlsClientCertificatePath());
    }

    public function testCloseBeforeInitIsIdempotentNoOp(): void
    {
        $client = new Client($this->configWithInvalidTlsClientCertificatePath());

        $client->close();
        $client->close();

        self::assertNull($client->responseMeta());
    }

    public function testCloseThrowsAndRecordsLogoutFailure(): void
    {
        $client = new Client($this->baseConfig());
        $transport = new class () implements Transport {
            public int $disconnectCalls = 0;

            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                ++$this->disconnectCalls;
            }

            public function writeFrame(string $payload): void
            {
                // Not needed for this unit test.
            }

            public function readFrame(): string
            {
                throw new \RuntimeException('logout read failed');
            }
        };

        $this->setClientTransportAndSession($client, $transport);
        $this->setClientLifecycleState($client, true, true);

        try {
            $client->close();
            self::fail('Expected close() to throw when logout fails.');
        } catch (\Throwable $throwable) {
            self::assertInstanceOf(\RNIDS\Exception\TransportException::class, $throwable);
            self::assertInstanceOf(\RNIDS\Exception\TransportException::class, $client->lastCloseError());
            self::assertStringContainsString('logout read failed', $throwable->getMessage());
        }

        self::assertSame(1, $transport->disconnectCalls);

        $client->close();
        self::assertSame(1, $transport->disconnectCalls);
    }

    public function testCloseThrowsAndRecordsDisconnectFailure(): void
    {
        $client = new Client($this->baseConfig());
        $transport = new class () implements Transport {
            /** @var list<string> */
            public array $writtenPayloads = [];

            /** @var list<string> */
            private array $responses = [
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                . '<response>'
                . '<result code="1500"><msg>Command completed successfully; ending session</msg></result>'
                . '<trID><clTRID>SESSION-00000002</clTRID><svTRID>SV-2</svTRID></trID>'
                . '</response>'
                . '</epp>',
            ];

            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                throw new \RuntimeException('disconnect failed');
            }

            public function writeFrame(string $payload): void
            {
                $this->writtenPayloads[] = $payload;
            }

            public function readFrame(): string
            {
                return \array_shift($this->responses) ?? '';
            }
        };

        $this->setClientTransportAndSession($client, $transport);
        $this->setClientLifecycleState($client, true, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('disconnect failed');

        try {
            $client->close();
        } finally {
            self::assertInstanceOf(\RuntimeException::class, $client->lastCloseError());
            self::assertCount(1, $transport->writtenPayloads);
            self::assertStringContainsString('<logout/>', $transport->writtenPayloads[0]);
        }
    }

    public function testDestructorSuppressesShutdownExceptionsAndRecordsError(): void
    {
        $client = new Client($this->baseConfig());
        $transport = new class () implements Transport {
            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                throw new \RuntimeException('disconnect failed during destruct');
            }

            public function writeFrame(string $payload): void
            {
                // Not needed for this unit test.
            }

            public function readFrame(): string
            {
                return '';
            }
        };

        $this->setClientTransportAndSession($client, $transport);
        $this->setClientLifecycleState($client, true, false);

        $client->__destruct();

        self::assertInstanceOf(\RuntimeException::class, $client->lastCloseError());
        self::assertSame('disconnect failed during destruct', $client->lastCloseError()?->getMessage());
    }

    /**
     * @return array<string, mixed>
     */
    private function baseConfig(): array
    {
        return [
            'host' => 'epp.example.rs',
            'password' => 'secret',
            'username' => 'client-id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function configWithInvalidTlsClientCertificatePath(): array
    {
        return [
            ...$this->baseConfig(),
            'tls' => [
                'clientCertificatePath' => '/this/path/does/not/exist/client.pem',
            ],
        ];
    }

    private function setClientTransportAndSession(Client $client, Transport $transport): void
    {
        $transportProperty = new \ReflectionProperty($client, 'transport');
        $transportProperty->setAccessible(true);
        $transportProperty->setValue($client, $transport);

        $sessionProperty = new \ReflectionProperty($client, 'sessionService');
        $sessionProperty->setAccessible(true);
        $sessionProperty->setValue($client, new SessionService($transport));
    }

    private function setClientLifecycleState(Client $client, bool $initialized, bool $loggedIn): void
    {
        $initializedProperty = new \ReflectionProperty($client, 'initialized');
        $initializedProperty->setAccessible(true);
        $initializedProperty->setValue($client, $initialized);

        $loggedInProperty = new \ReflectionProperty($client, 'loggedIn');
        $loggedInProperty->setAccessible(true);
        $loggedInProperty->setValue($client, $loggedIn);
    }
}
