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

    public function testInfoSendsDomainInfoCommandAndMapsParsedResponse(): void
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
                    . '<domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:roid>D1-RS</domain:roid>'
                    . '<domain:status s="ok">Active</domain:status>'
                    . '<domain:registrant>REG-1</domain:registrant>'
                    . '<domain:contact type="admin">ADM-1</domain:contact>'
                    . '<domain:ns><domain:hostObj>ns1.example.rs</domain:hostObj></domain:ns>'
                    . '</domain:infData>'
                    . '</resData>'
                    . '<extension>'
                    . '<domainExt:domain-ext xmlns:domainExt="http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0">'
                    . '<domainExt:isWhoisPrivacy>1</domainExt:isWhoisPrivacy>'
                    . '<domainExt:operationMode>normal</domainExt:operationMode>'
                    . '</domainExt:domain-ext>'
                    . '</extension>'
                    . '<trID><clTRID>DOMAIN-00000002</clTRID><svTRID>SV-2</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000002';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->info([
            'hosts' => 'sub',
            'name' => 'example.rs',
        ]);

        self::assertStringContainsString(
            '<domain:name hosts="sub">example.rs</domain:name>',
            $transport->writtenPayload,
        );
        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame('example.rs', $result['info']['name']);
        self::assertSame('D1-RS', $result['info']['roid']);
        self::assertSame('ok', $result['info']['statuses'][0]['value']);
        self::assertSame('Active', $result['info']['statuses'][0]['description']);
        self::assertSame('REG-1', $result['info']['registrant']);
        self::assertSame('admin', $result['info']['contacts'][0]['type']);
        self::assertSame('ADM-1', $result['info']['contacts'][0]['handle']);
        self::assertSame('ns1.example.rs', $result['info']['nameservers'][0]['name']);
        self::assertSame('1', $result['info']['extension']['isWhoisPrivacy']);
        self::assertSame('normal', $result['info']['extension']['operationMode']);
    }

    public function testInfoThrowsForMissingName(): void
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
        $this->expectExceptionMessage('Domain info request key "name" must be a non-empty string.');

        $service->info([]);
    }

    public function testInfoThrowsForInvalidHostsParameter(): void
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
            'Domain info request key "hosts" must be one of "all", "del", "sub", or "none".',
        );

        $service->info([
            'hosts' => 'invalid',
            'name' => 'example.rs',
        ]);
    }

    public function testRegisterSendsDomainCreateCommandAndMapsParsedResponse(): void
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
                    . '<domain:creData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:crDate>2026-02-01T00:00:00.0Z</domain:crDate>'
                    . '<domain:exDate>2027-02-01T00:00:00.0Z</domain:exDate>'
                    . '</domain:creData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000003</clTRID><svTRID>SV-3</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000003';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->register([
            'authInfo' => 'secret',
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                [ 'handle' => 'TEC-1', 'type' => 'tech' ],
            ],
            'extension' => [
                'dnsSec' => true,
                'isWhoisPrivacy' => true,
                'notifyAdmin' => false,
                'operationMode' => 'secure',
                'remark' => 'Note',
            ],
            'name' => 'example.rs',
            'nameservers' => [
                [ 'name' => 'ns1.example.rs' ],
                [ 'addresses' => [ '192.0.2.2' ], 'name' => 'ns2.example.rs' ],
            ],
            'period' => 1,
            'periodUnit' => 'y',
            'registrant' => 'REG-1',
        ]);

        self::assertStringContainsString(
            '<domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">',
            $transport->writtenPayload,
        );
        self::assertStringContainsString('<domain:name>example.rs</domain:name>', $transport->writtenPayload);
        self::assertStringContainsString(
            '<domain:contact type="admin">ADM-1</domain:contact>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<domain:contact type="tech">TEC-1</domain:contact>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<domainExt:operationMode>secure</domainExt:operationMode>',
            $transport->writtenPayload,
        );
        self::assertSame(1000, $result['metadata']['resultCode']);
        self::assertSame('example.rs', $result['creation']['name']);
        self::assertSame('2026-02-01T00:00:00.0Z', $result['creation']['createDate']);
        self::assertSame('2027-02-01T00:00:00.0Z', $result['creation']['expirationDate']);
    }

    public function testRegisterThrowsWhenContactsAreMissingRequiredTypes(): void
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
            'Domain register request contacts must include at least one "admin" and one "tech" contact.',
        );

        $service->register([
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
            ],
            'name' => 'example.rs',
            'registrant' => 'REG-1',
        ]);
    }

    public function testRegisterThrowsForInvalidOperationMode(): void
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
            'Domain register request extension key "operationMode" must be "normal" or "secure" when provided.',
        );

        $service->register([
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                [ 'handle' => 'TEC-1', 'type' => 'tech' ],
            ],
            'extension' => [
                'operationMode' => 'invalid',
            ],
            'name' => 'example.rs',
            'registrant' => 'REG-1',
        ]);
    }
}
