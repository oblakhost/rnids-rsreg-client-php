<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Domain\DomainService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

final class DomainServiceTest extends TestCase
{
    public function testCheckSendsDomainCheckCommandAndMapsParsedResponse(): void
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
                    . '<result code="1000"><msg>Command completed successfully</msg></result>'
                    . '<resData>'
                    . '<domain:chkData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:cd><domain:name avail="1">example.rs</domain:name></domain:cd>'
                    . '</domain:chkData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000001';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->check([ 'names' => [ 'example.rs' ] ]);

        self::assertStringContainsString('<domain:name>example.rs</domain:name>', $transport->writtenPayload);
        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame('Command completed successfully', $result['metadata']['message']);
        self::assertCount(1, $result['items']);
        self::assertSame('example.rs', $result['items'][0]['name']);
        self::assertTrue($result['items'][0]['available']);
    }

    public function testCheckThrowsForMissingNames(): void
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

        $service = new DomainService($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain check request key "names" must be a non-empty list of strings.',
        );

        $service->check([]);
    }
}
