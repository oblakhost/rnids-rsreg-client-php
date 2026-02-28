<?php

declare(strict_types=1);

namespace Tests\Unit\Session;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RNIDS\Connection\Transport;
use RNIDS\Session\SessionService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

#[Group('unit')]
final class SessionServiceTest extends TestCase
{
    public function testHelloSendsHelloFrameAndMapsGreetingData(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                // Not needed for this unit test.
            }

            public function writeFrame(string $payload): void
            {
                $this->writtenPayload = $payload;
            }

            public function readFrame(): string
            {
                return '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<greeting>'
                    . '<svID>RNIDS EPP</svID>'
                    . '<svDate>2026-02-27T00:00:00.0Z</svDate>'
                    . '<svcMenu>'
                    . '<version>1.0</version>'
                    . '<lang>en</lang>'
                    . '<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>'
                    . '</svcMenu>'
                    . '</greeting>'
                    . '</epp>';
            }
        };

        $service = new SessionService($transport);
        $result = $service->hello();

        self::assertStringContainsString('<hello/>', $transport->writtenPayload);
        self::assertSame('Greeting', $result['metadata']['message']);
        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame('RNIDS EPP', $result['greeting']['serverId']);
        self::assertSame('2026-02-27T00:00:00.0Z', $result['greeting']['serverDate']);
        self::assertSame([ '1.0' ], $result['greeting']['versions']);
        self::assertSame([ 'en' ], $result['greeting']['languages']);
    }

    public function testPollReqSendsPollFrameAndMapsQueueData(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                // Not needed for this unit test.
            }

            public function writeFrame(string $payload): void
            {
                $this->writtenPayload = $payload;
            }

            public function readFrame(): string
            {
                return '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1301"><msg>Command completed successfully; ack to dequeue</msg></result>'
                    . '<msgQ count="1" id="MSG-1">'
                    . '<qDate>2026-02-27T00:00:00.0Z</qDate>'
                    . '<msg>M100:Domain example.rs registration successful</msg>'
                    . '</msgQ>'
                    . '<trID><clTRID>SESSION-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'SESSION-00000001';
            }
        };

        $service = new SessionService($transport, null, $generator);
        $result = $service->poll();

        self::assertStringContainsString('<poll op="req"/>', $transport->writtenPayload);
        self::assertSame(1301, $result['metadata']['resultCode']);
        self::assertSame(1, $result['queue']['count']);
        self::assertSame('MSG-1', $result['queue']['messageId']);
        self::assertSame('2026-02-27T00:00:00.0Z', $result['queue']['queueDate']);
    }

    public function testPollAckRequiresMessageId(): void
    {
        $transport = new class () implements Transport {
            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                // Not needed for this unit test.
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

        $service = new SessionService($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "messageId" must be a non-empty string.');

        $service->poll([ 'operation' => 'ack' ]);
    }
}
