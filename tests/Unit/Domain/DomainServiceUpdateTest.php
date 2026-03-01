<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Domain\DomainService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

#[Group('unit')]
final class DomainServiceUpdateTest extends TestCase
{
    public function testUpdateSendsDomainUpdateCommandAndMapsResponse(): void
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
                    . '<trID><clTRID>DOMAIN-00000099</clTRID><svTRID>SV-99</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000099';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->update([
            'add' => [
                'contacts' => [
                    [ 'handle' => 'ADM-2', 'type' => 'admin' ],
                ],
            ],
            'name' => 'example.rs',
            'remove' => [
                'contacts' => [
                    [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                ],
            ],
        ]);

        self::assertStringContainsString('<domain:update', $transport->writtenPayload);
        self::assertStringContainsString(
            '<domain:add><domain:contact type="admin">ADM-2</domain:contact></domain:add>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<domain:rem><domain:contact type="admin">ADM-1</domain:contact></domain:rem>',
            $transport->writtenPayload,
        );
        self::assertSame([], $result);
    }

    public function testUpdateThrowsWhenNoMutationsProvided(): void
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
            'Domain update request must include at least one of "add", "remove", "registrant", or "authInfo".',
        );

        $service->update([ 'name' => 'example.rs' ]);
    }
}
