<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContactFixtureFactory;

#[Group('unit')]
final class ContactFixtureFactoryTest extends TestCase
{
    public function testIndividualPayloadIsDeterministicForSeed(): void
    {
        $factory = ContactFixtureFactory::forSeed('seed-13');

        $payloadA = $factory->individualCreatePayload();
        $payloadB = $factory->individualCreatePayload();

        self::assertSame($payloadA, $payloadB);
        self::assertSame('0', $payloadA['extension']['isLegalEntity']);
    }

    public function testCompanyPayloadContainsLegalEntityAndVatNo(): void
    {
        $factory = ContactFixtureFactory::forSeed('seed-13');
        $payload = $factory->companyCreatePayload();

        self::assertSame('1', $payload['extension']['isLegalEntity']);
        self::assertArrayHasKey('vatNo', $payload['extension']);
        self::assertNotSame('', $payload['extension']['vatNo']);
    }

    public function testRunTokenProducesDistinctStableIds(): void
    {
        $base = ContactFixtureFactory::forSeed('seed-13');
        $withToken = $base->withRunToken('r1');

        self::assertNotSame($base->contactId('individual'), $withToken->contactId('individual'));
        self::assertSame($withToken->contactId('individual'), $withToken->contactId('individual'));
    }

    public function testDomainPayloadsMapAdminTechAndRegistrantChangeShapes(): void
    {
        $factory = ContactFixtureFactory::forSeed('seed-13');

        $adminTechPayload = $factory->domainAdminTechChangePayload('example.rs', 'ADM-1', 'TEC-1');
        $registrantPayload = $factory->domainRegistrantChangePayload('example.rs', 'REG-2');

        self::assertSame('admin', $adminTechPayload['add']['contacts'][0]['type']);
        self::assertSame('tech', $adminTechPayload['add']['contacts'][1]['type']);
        self::assertSame('REG-2', $registrantPayload['registrant']);
    }
}
