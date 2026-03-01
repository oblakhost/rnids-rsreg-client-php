<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainRegisterRequestFactory;

#[Group('unit')]
final class DomainRegisterRequestFactoryTest extends TestCase
{
    public function testFromArrayBuildsTypedRequestForValidPayload(): void
    {
        $factory = new DomainRegisterRequestFactory();

        $request = $factory->fromArray([
            'authInfo' => 'domain-secret',
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                [ 'handle' => 'TECH-1', 'type' => 'tech' ],
                [ 'handle' => 'BILL-1', 'type' => 'billing' ],
            ],
            'extension' => [
                'dnsSec' => true,
                'isWhoisPrivacy' => false,
                'notifyAdmin' => true,
                'operationMode' => 'secure',
                'remark' => 'Registrant supplied note',
            ],
            'name' => 'example.rs',
            'nameservers' => [
                [ 'name' => 'ns1.example.rs' ],
                [
                    'addresses' => [ '192.0.2.10', '2001:db8::10' ],
                    'name' => 'ns2.example.rs',
                ],
            ],
            'period' => 2,
            'periodUnit' => 'm',
            'registrant' => 'REG-1',
        ]);

        self::assertSame('example.rs', $request->name);
        self::assertSame(2, $request->period);
        self::assertSame('m', $request->periodUnit);
        self::assertSame('REG-1', $request->registrant);
        self::assertSame('domain-secret', $request->authInfo);
        self::assertCount(3, $request->contacts);
        self::assertCount(2, $request->nameservers);
        self::assertSame('ns2.example.rs', $request->nameservers[1]->name);
        self::assertSame([ '192.0.2.10', '2001:db8::10' ], $request->nameservers[1]->addresses);
        self::assertNotNull($request->extension);
        self::assertSame('Registrant supplied note', $request->extension->remark);
        self::assertFalse($request->extension->isWhoisPrivacy);
        self::assertSame('secure', $request->extension->operationMode);
        self::assertTrue($request->extension->notifyAdmin);
        self::assertTrue($request->extension->dnsSec);
    }

    public function testFromArrayUsesDefaultsForOptionalKeys(): void
    {
        $factory = new DomainRegisterRequestFactory();

        $request = $factory->fromArray([
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                [ 'handle' => 'TECH-1', 'type' => 'tech' ],
            ],
            'name' => 'example.rs',
            'registrant' => 'REG-1',
        ]);

        self::assertNull($request->period);
        self::assertSame('y', $request->periodUnit);
        self::assertSame([], $request->nameservers);
        self::assertNull($request->authInfo);
        self::assertNull($request->extension);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testFromArrayRejectsInvalidPayload(array $payload, string $errorMessage): void
    {
        $factory = new DomainRegisterRequestFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $factory->fromArray($payload);
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>, errorMessage: string}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        $valid = self::validPayload();

        yield 'missing name' => [
            'payload' => self::without($valid, 'name'),
            'errorMessage' => 'Domain register request key "name" must be a non-empty string.',
        ];

        $invalidPeriod = $valid;
        $invalidPeriod['period'] = 0;
        yield 'invalid period' => [
            'payload' => $invalidPeriod,
            'errorMessage' => 'Domain register request key "period" must be a positive integer when provided.',
        ];

        $invalidUnit = $valid;
        $invalidUnit['periodUnit'] = 'w';
        yield 'invalid period unit' => [
            'payload' => $invalidUnit,
            'errorMessage' => 'Domain register request key "periodUnit" must be either "y" or "m" when provided.',
        ];

        $invalidNameservers = $valid;
        $invalidNameservers['nameservers'] = 'ns1.example.rs';
        yield 'nameservers must be list' => [
            'payload' => $invalidNameservers,
            'errorMessage' => 'Domain register request key "nameservers" must be a list.',
        ];

        $invalidNameserverEntry = $valid;
        $invalidNameserverEntry['nameservers'] = [ 'ns1.example.rs' ];
        yield 'nameserver entry must be array' => [
            'payload' => $invalidNameserverEntry,
            'errorMessage' => 'Domain register request nameserver at index 0 must be an array.',
        ];

        $invalidNameserverName = $valid;
        $invalidNameserverName['nameservers'] = [ [ 'name' => '' ] ];
        yield 'nameserver name required' => [
            'payload' => $invalidNameserverName,
            'errorMessage' => 'Domain register request nameserver at index 0 must include non-empty "name".',
        ];

        $invalidNameserverAddresses = $valid;
        $invalidNameserverAddresses['nameservers'] = [ [ 'addresses' => 'bad', 'name' => 'ns1.example.rs' ] ];
        yield 'nameserver addresses must be list' => [
            'payload' => $invalidNameserverAddresses,
            'errorMessage' => 'Domain register request nameserver at index 0 field "addresses" must be a list.',
        ];

        $invalidNameserverAddressItem = $valid;
        $invalidNameserverAddressItem['nameservers'] = [
            [ 'addresses' => [ '192.0.2.10', '' ], 'name' => 'ns1.example.rs' ],
        ];
        yield 'nameserver address item required' => [
            'payload' => $invalidNameserverAddressItem,
            'errorMessage' => 'Domain register request nameserver at index 0 has invalid address at index 1.',
        ];

        $invalidContacts = self::without($valid, 'contacts');
        yield 'contacts required' => [
            'payload' => $invalidContacts,
            'errorMessage' => 'Domain register request key "contacts" must be a non-empty list.',
        ];

        $invalidContactEntry = $valid;
        $invalidContactEntry['contacts'] = [ 'bad' ];
        yield 'contact entry must be array' => [
            'payload' => $invalidContactEntry,
            'errorMessage' => 'Domain register request contact at index 0 must be an array.',
        ];

        $invalidContactType = $valid;
        $invalidContactType['contacts'] = [
            [ 'handle' => 'ADM-1', 'type' => 'owner' ],
            [ 'handle' => 'TECH-1', 'type' => 'tech' ],
        ];
        yield 'contact type must be known' => [
            'payload' => $invalidContactType,
            'errorMessage' => 'Domain register request contact at index 0 has invalid "type" (allowed: admin, tech, billing).',
        ];

        $invalidContactHandle = $valid;
        $invalidContactHandle['contacts'] = [
            [ 'handle' => '', 'type' => 'admin' ],
            [ 'handle' => 'TECH-1', 'type' => 'tech' ],
        ];
        yield 'contact handle required' => [
            'payload' => $invalidContactHandle,
            'errorMessage' => 'Domain register request contact at index 0 must include non-empty "handle".',
        ];

        $missingRequiredTypes = $valid;
        $missingRequiredTypes['contacts'] = [ [ 'handle' => 'BILL-1', 'type' => 'billing' ] ];
        yield 'required contact types missing' => [
            'payload' => $missingRequiredTypes,
            'errorMessage' => 'Domain register request contacts must include at least one "admin" and one "tech" contact.',
        ];

        $invalidAuthInfo = $valid;
        $invalidAuthInfo['authInfo'] = '';
        yield 'auth info must be non-empty when provided' => [
            'payload' => $invalidAuthInfo,
            'errorMessage' => 'Domain register request key "authInfo" must be a non-empty string when provided.',
        ];

        $invalidExtensionType = $valid;
        $invalidExtensionType['extension'] = 'bad';
        yield 'extension must be array' => [
            'payload' => $invalidExtensionType,
            'errorMessage' => 'Domain register request key "extension" must be an array when provided.',
        ];

        $invalidRemark = $valid;
        $invalidRemark['extension'] = [ 'remark' => '' ];
        yield 'extension remark must be non-empty' => [
            'payload' => $invalidRemark,
            'errorMessage' => 'Domain register request extension key "remark" must be a non-empty string when provided.',
        ];

        $invalidOperationMode = $valid;
        $invalidOperationMode['extension'] = [ 'operationMode' => 'strict' ];
        yield 'operation mode must be known' => [
            'payload' => $invalidOperationMode,
            'errorMessage' => 'Domain register request extension key "operationMode" must be "normal" or "secure" when provided.',
        ];

        $invalidBoolFlag = $valid;
        $invalidBoolFlag['extension'] = [ 'dnsSec' => '1' ];
        yield 'extension bools must be booleans' => [
            'payload' => $invalidBoolFlag,
            'errorMessage' => 'Domain register request extension key "dnsSec" must be a boolean when provided.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function validPayload(): array
    {
        return [
            'contacts' => [
                [ 'handle' => 'ADM-1', 'type' => 'admin' ],
                [ 'handle' => 'TECH-1', 'type' => 'tech' ],
            ],
            'name' => 'example.rs',
            'registrant' => 'REG-1',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function without(array $payload, string $key): array
    {
        unset($payload[$key]);

        return $payload;
    }
}
