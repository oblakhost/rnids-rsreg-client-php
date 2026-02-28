<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Domain\DomainService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

#[Group('unit')]
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
        self::assertCount(1, $result);
        self::assertSame('example.rs', $result[0]['name']);
        self::assertTrue($result[0]['available']);
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

    public function testCheckAcceptsSingleDomainString(): void
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

        $service = new DomainService($transport);
        $result = $service->check('example.rs');

        self::assertStringContainsString('<domain:name>example.rs</domain:name>', $transport->writtenPayload);
        self::assertSame('example.rs', $result[0]['name']);
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
        $result = $service->info('example.rs', 'sub');

        self::assertStringContainsString(
            '<domain:name hosts="sub">example.rs</domain:name>',
            $transport->writtenPayload,
        );
        self::assertSame('example.rs', $result['name']);
        self::assertSame('D1-RS', $result['roid']);
        self::assertSame('ok', $result['statuses'][0]['value']);
        self::assertSame('Active', $result['statuses'][0]['description']);
        self::assertSame('REG-1', $result['registrant']);
        self::assertSame('admin', $result['contacts'][0]['type']);
        self::assertSame('ADM-1', $result['contacts'][0]['handle']);
        self::assertSame('ns1.example.rs', $result['nameservers'][0]['name']);
        self::assertSame('1', $result['extension']['isWhoisPrivacy']);
        self::assertSame('normal', $result['extension']['operationMode']);
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
        $this->expectExceptionMessage('Domain name must be a non-empty string.');

        $service->info('');
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

        $service->info('example.rs', 'invalid');
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
        self::assertSame('example.rs', $result['name']);
        self::assertSame('2026-02-01T00:00:00.0Z', $result['createDate']);
        self::assertSame('2027-02-01T00:00:00.0Z', $result['expirationDate']);
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

    public function testRegisterAcceptsSimplifiedArgumentsAndRequiresNameservers(): void
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

        $service = new DomainService($transport);

        $result = $service->register(
            'example.rs',
            'REG-1',
            'ADM-1',
            'TEC-1',
            [ 'ns1.example.rs', 'ns2.example.rs' ],
            1,
            null,
            [ 'operationMode' => 'normal' ],
        );

        self::assertStringContainsString(
            '<domain:hostObj>ns1.example.rs</domain:hostObj>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<domain:contact type="admin">ADM-1</domain:contact>',
            $transport->writtenPayload,
        );
        self::assertSame('example.rs', $result['name']);
    }

    public function testRegisterSimplifiedThrowsWithoutNameservers(): void
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
        $this->expectExceptionMessage('Domain register simplified API requires at least one nameserver.');

        $service->register('example.rs', 'REG-1', 'ADM-1', 'TEC-1');
    }

    public function testRenewSendsDomainRenewCommandAndMapsParsedResponse(): void
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
                    . '<domain:renData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:exDate>2028-02-01T00:00:00.0Z</domain:exDate>'
                    . '</domain:renData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000004</clTRID><svTRID>SV-4</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000004';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->renew([
            'currentExpirationDate' => '2027-02-01',
            'name' => 'example.rs',
            'period' => 1,
            'periodUnit' => 'y',
        ]);

        self::assertStringContainsString('<domain:renew', $transport->writtenPayload);
        self::assertStringContainsString(
            '<domain:curExpDate>2027-02-01</domain:curExpDate>',
            $transport->writtenPayload,
        );
        self::assertSame('example.rs', $result['name']);
        self::assertSame('2028-02-01T00:00:00.0Z', $result['expirationDate']);
    }

    public function testDeleteSendsDomainDeleteCommandAndMapsParsedResponse(): void
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
                    . '<trID><clTRID>DOMAIN-00000005</clTRID><svTRID>SV-5</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000005';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->delete('example.rs');

        self::assertStringContainsString('<domain:delete', $transport->writtenPayload);
        self::assertSame([], $result);
    }

    public function testRenewAcceptsSimplifiedDomainAndYearsAndResolvesCurrentExpirationDate(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            /** @var list<string> */
            private array $responses;

            public function __construct()
            {
                $this->responses = [
                    '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1000"><msg>Command completed successfully</msg></result>'
                    . '<resData>'
                    . '<domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:exDate>2027-02-01T00:00:00.0Z</domain:exDate>'
                    . '</domain:infData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000010</clTRID><svTRID>SV-10</svTRID></trID>'
                    . '</response>'
                    . '</epp>',
                    '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1000"><msg>Command completed successfully</msg></result>'
                    . '<resData>'
                    . '<domain:renData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:exDate>2028-02-01T00:00:00.0Z</domain:exDate>'
                    . '</domain:renData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000011</clTRID><svTRID>SV-11</svTRID></trID>'
                    . '</response>'
                    . '</epp>',
                ];
            }

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
                return \array_shift($this->responses) ?? '';
            }
        };

        $service = new DomainService($transport);
        $result = $service->renew('example.rs', 1);

        self::assertStringContainsString(
            '<domain:curExpDate>2027-02-01</domain:curExpDate>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<domain:period unit="y">1</domain:period>',
            $transport->writtenPayload,
        );
        self::assertSame('2028-02-01T00:00:00.0Z', $result['expirationDate']);
    }

    public function testTransferSendsDomainTransferCommandAndMapsParsedResponse(): void
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
                    . '<result code="1001"><msg>Command completed successfully; action pending</msg></result>'
                    . '<resData>'
                    . '<domain:trnData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                    . '<domain:name>example.rs</domain:name>'
                    . '<domain:trStatus>pending</domain:trStatus>'
                    . '<domain:reID>REQ-1</domain:reID>'
                    . '<domain:reDate>2026-03-01T10:00:00.0Z</domain:reDate>'
                    . '<domain:acID>ACT-1</domain:acID>'
                    . '<domain:acDate>2026-03-06T10:00:00.0Z</domain:acDate>'
                    . '<domain:exDate>2027-02-01T00:00:00.0Z</domain:exDate>'
                    . '</domain:trnData>'
                    . '</resData>'
                    . '<trID><clTRID>DOMAIN-00000006</clTRID><svTRID>SV-6</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'DOMAIN-00000006';
            }
        };

        $service = new DomainService($transport, null, $generator);
        $result = $service->transfer([
            'authInfo' => 'secret',
            'name' => 'example.rs',
            'operation' => 'request',
            'period' => 1,
            'periodUnit' => 'y',
        ]);

        self::assertStringContainsString('<transfer op="request">', $transport->writtenPayload);
        self::assertSame('example.rs', $result['name']);
        self::assertSame('pending', $result['transferStatus']);
    }

    public function testTransferThrowsForInvalidOperation(): void
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
        $expectedMessage = 'Domain transfer request key "operation" must be one of '
            . '"request", "query", "cancel", "approve", or "reject".';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $service->transfer([
            'name' => 'example.rs',
            'operation' => 'invalid',
        ]);
    }
}
