<?php

declare(strict_types=1);

namespace Tests\Unit\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Host\HostService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

#[Group('unit')]
final class HostServiceTest extends TestCase
{
    public function testCheckSendsHostCheckCommandAndMapsResponse(): void
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
                    . '<resData><host:chkData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
                    . '<host:cd><host:name avail="1">ns1.example.rs</host:name></host:cd>'
                    . '</host:chkData></resData>'
                    . '<trID><clTRID>HOST-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'HOST-00000001';
            }
        };

        $service = new HostService($transport, null, $generator);
        $result = $service->check([ 'names' => [ 'ns1.example.rs' ] ]);

        self::assertStringContainsString('<host:name>ns1.example.rs</host:name>', $transport->writtenPayload);
        self::assertSame('ns1.example.rs', $result[0]['name']);
        self::assertTrue($result[0]['available']);
    }

    public function testCheckAcceptsSingleHostString(): void
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
                    . '<resData><host:chkData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
                    . '<host:cd><host:name avail="1">ns1.example.rs</host:name></host:cd>'
                    . '</host:chkData></resData>'
                    . '<trID><clTRID>HOST-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new HostService($transport);
        $result = $service->check('ns1.example.rs');

        self::assertStringContainsString('<host:name>ns1.example.rs</host:name>', $transport->writtenPayload);
        self::assertSame('ns1.example.rs', $result[0]['name']);
    }

    public function testInfoSendsHostInfoCommandAndMapsResponse(): void
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
                    . '<resData><host:infData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
                    . '<host:name>ns1.example.rs</host:name>'
                    . '<host:roid>H-1</host:roid>'
                    . '<host:status s="ok">Active</host:status>'
                    . '<host:addr ip="v4">192.0.2.1</host:addr>'
                    . '</host:infData></resData>'
                    . '<trID><clTRID>HOST-00000002</clTRID><svTRID>SV-2</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'HOST-00000002';
            }
        };

        $service = new HostService($transport, null, $generator);
        $result = $service->info('ns1.example.rs');

        self::assertStringContainsString('<host:info', $transport->writtenPayload);
        self::assertSame('ns1.example.rs', $result['name']);
        self::assertSame('ok', $result['statuses'][0]['value']);
        self::assertSame('192.0.2.1', $result['addresses'][0]['address']);
    }

    public function testCreateSendsHostCreateCommandAndMapsResponse(): void
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
                    . '<resData><host:creData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
                    . '<host:name>ns1.example.rs</host:name>'
                    . '<host:crDate>2026-02-01T00:00:00.0Z</host:crDate>'
                    . '</host:creData></resData>'
                    . '<trID><clTRID>HOST-00000003</clTRID><svTRID>SV-3</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'HOST-00000003';
            }
        };

        $service = new HostService($transport, null, $generator);
        $result = $service->create([
            'addresses' => [ [ 'address' => '192.0.2.1', 'ipVersion' => 'v4' ] ],
            'name' => 'ns1.example.rs',
        ]);

        self::assertStringContainsString('<host:create', $transport->writtenPayload);
        self::assertSame('ns1.example.rs', $result['name']);
        self::assertSame('2026-02-01T00:00:00.0Z', $result['createDate']);
    }

    public function testCreateAcceptsHostnameAndIpArguments(): void
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
                    . '<resData><host:creData xmlns:host="urn:ietf:params:xml:ns:host-1.0">'
                    . '<host:name>ns1.example.rs</host:name>'
                    . '<host:crDate>2026-02-01T00:00:00.0Z</host:crDate>'
                    . '</host:creData></resData>'
                    . '<trID><clTRID>HOST-00000003</clTRID><svTRID>SV-3</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new HostService($transport);
        $result = $service->create('ns1.example.rs', '192.0.2.1', '2001:db8::1');

        self::assertStringContainsString(
            '<host:addr ip="v4">192.0.2.1</host:addr>',
            $transport->writtenPayload,
        );
        self::assertStringContainsString(
            '<host:addr ip="v6">2001:db8::1</host:addr>',
            $transport->writtenPayload,
        );
        self::assertSame('ns1.example.rs', $result['name']);
    }

    public function testUpdateSendsHostUpdateCommandAndMapsResponse(): void
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
                    . '<trID><clTRID>HOST-00000004</clTRID><svTRID>SV-4</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'HOST-00000004';
            }
        };

        $service = new HostService($transport, null, $generator);
        $result = $service->update([
            'add' => [
                'addresses' => [ [ 'address' => '192.0.2.2', 'ipVersion' => 'v4' ] ],
                'statuses' => [ 'ok' ],
            ],
            'name' => 'ns1.example.rs',
            'newName' => 'ns2.example.rs',
        ]);

        self::assertStringContainsString('<host:update', $transport->writtenPayload);
        self::assertStringContainsString(
            '<host:chg><host:name>ns2.example.rs</host:name></host:chg>',
            $transport->writtenPayload,
        );
        self::assertSame([], $result);
    }

    public function testDeleteSendsHostDeleteCommandAndMapsResponse(): void
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
                    . '<trID><clTRID>HOST-00000005</clTRID><svTRID>SV-5</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'HOST-00000005';
            }
        };

        $service = new HostService($transport, null, $generator);
        $result = $service->delete('ns1.example.rs');

        self::assertStringContainsString('<host:delete', $transport->writtenPayload);
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

        $service = new HostService($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Host update request must include at least one of "add", "remove", or "newName".',
        );

        $service->update([ 'name' => 'ns1.example.rs' ]);
    }
}
