<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Contact\ContactService;
use RNIDS\Xml\ClTrid\ClTridGenerator;

#[Group('unit')]
final class ContactServiceTest extends TestCase
{
    public function testCheckSendsContactCheckCommandAndMapsResponse(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
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
                    . '<result code="1000"><msg>OK</msg></result>'
                    . '<resData><contact:chkData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
                    . '<contact:cd><contact:id avail="1">C-100</contact:id></contact:cd>'
                    . '</contact:chkData></resData>'
                    . '<trID><clTRID>CONTACT-00000001</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $generator = new class () implements ClTridGenerator {
            public function nextId(): string
            {
                return 'CONTACT-00000001';
            }
        };

        $service = new ContactService($transport, null, $generator);
        $result = $service->check('C-100');

        self::assertStringContainsString('<contact:id>C-100</contact:id>', $transport->writtenPayload);
        self::assertSame('C-100', $result[0]['id']);
        self::assertTrue($result[0]['available']);
    }

    public function testCreateSendsContactCreateCommandAndMapsResponse(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
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
                    . '<result code="1000"><msg>OK</msg></result>'
                    . '<resData><contact:creData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
                    . '<contact:id>C-200</contact:id>'
                    . '<contact:crDate>2026-03-01T00:00:00.0Z</contact:crDate>'
                    . '</contact:creData></resData>'
                    . '<trID><clTRID>CONTACT-00000002</clTRID><svTRID>SV-2</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new ContactService($transport);
        $result = $service->create([
            'email' => 'person@example.rs',
            'id' => 'C-200',
            'postalInfo' => [
                'address' => [
                    'city' => 'Belgrade',
                    'countryCode' => 'RS',
                    'streets' => [ 'Main 1' ],
                ],
                'name' => 'Person Example',
            ],
        ]);

        self::assertStringContainsString('<contact:create', $transport->writtenPayload);
        self::assertStringContainsString('<contact:id>OBL-C-200</contact:id>', $transport->writtenPayload);
        self::assertSame('C-200', $result['id']);
        self::assertInstanceOf(\DateTimeImmutable::class, $result['createDate']);
        self::assertSame('2026-03-01T00:00:00+00:00', $result['createDate']?->format('c'));
    }

    public function testInfoSendsContactInfoCommandAndMapsResponse(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
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
                    . '<result code="1000"><msg>OK</msg></result>'
                    . '<resData><contact:infData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">'
                    . '<contact:id>C-300</contact:id>'
                    . '<contact:roid>C300-RS</contact:roid>'
                    . '<contact:status s="ok">Active</contact:status>'
                    . '<contact:postalInfo type="loc">'
                    . '<contact:name>Person Example</contact:name>'
                    . '<contact:addr>'
                    . '<contact:street>Main 1</contact:street>'
                    . '<contact:city>Belgrade</contact:city>'
                    . '<contact:cc>RS</contact:cc>'
                    . '</contact:addr>'
                    . '</contact:postalInfo>'
                    . '<contact:email>person@example.rs</contact:email>'
                    . '</contact:infData></resData>'
                    . '<extension>'
                    . '<contactExt:contact-ext xmlns:contactExt="http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0">'
                    . '<contactExt:ident>12345</contactExt:ident>'
                    . '</contactExt:contact-ext>'
                    . '</extension>'
                    . '<trID><clTRID>CONTACT-00000003</clTRID><svTRID>SV-3</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new ContactService($transport);
        $result = $service->info('C-300');

        self::assertStringContainsString('<contact:info', $transport->writtenPayload);
        self::assertSame('C-300', $result['id']);
        self::assertSame('ok', $result['statuses'][0]);
        self::assertSame('loc', $result['postalType']);
        self::assertSame('Person Example', $result['postalName']);
        self::assertSame('Main 1', $result['postalStreet1']);
        self::assertNull($result['postalStreet2']);
        self::assertNull($result['postalStreet3']);
        self::assertSame('Belgrade', $result['postalCity']);
        self::assertSame('RS', $result['postalCountryCode']);
        self::assertSame('Belgrade', $result['postalInfo']['address']['city']);
        self::assertSame('12345', $result['ident']);
    }

    public function testUpdateSendsContactUpdateCommandAndReturnsEmptyArray(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
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
                    . '<result code="1000"><msg>OK</msg></result>'
                    . '<trID><clTRID>CONTACT-00000004</clTRID><svTRID>SV-4</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new ContactService($transport);
        $result = $service->update([
            'email' => 'updated@example.rs',
            'id' => 'C-400',
        ]);

        self::assertStringContainsString('<contact:update', $transport->writtenPayload);
        self::assertStringContainsString('<contact:id>OBL-C-400</contact:id>', $transport->writtenPayload);
        self::assertSame([], $result);
    }

    public function testDeleteSendsContactDeleteCommandAndReturnsEmptyArray(): void
    {
        $transport = new class () implements Transport {
            public string $writtenPayload = '';

            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
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
                    . '<result code="1000"><msg>OK</msg></result>'
                    . '<trID><clTRID>CONTACT-00000005</clTRID><svTRID>SV-5</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $service = new ContactService($transport);
        $result = $service->delete('C-500');

        self::assertStringContainsString('<contact:delete', $transport->writtenPayload);
        self::assertSame([], $result);
    }

    public function testUpdateThrowsWhenNoMutationProvided(): void
    {
        $transport = new class () implements Transport {
            public function connect(): void
            {
                // No-op for transport test double.
            }

            public function disconnect(): void
            {
                // No-op for transport test double.
            }

            public function writeFrame(string $payload): void
            {
                // No-op for transport test double.
            }

            public function readFrame(): string
            {
                return '';
            }
        };

        $service = new ContactService($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact update request must include at least one change field.');

        $service->update([ 'id' => 'C-600' ]);
    }
}
