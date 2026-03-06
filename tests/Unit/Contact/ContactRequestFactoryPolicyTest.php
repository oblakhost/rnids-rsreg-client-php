<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactRequestFactory;

#[Group('unit')]
final class ContactRequestFactoryPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{id: mixed, expectedPrefixOrValue: string, expectExact: bool}>
     */
    public static function createIdNormalizationProvider(): iterable
    {
        yield 'missing id generates policy id' => [
            'expectedPrefixOrValue' => 'OBL-',
            'expectExact' => false,
            'id' => null,
        ];
        yield 'whitespace id generates policy id' => [
            'expectedPrefixOrValue' => 'OBL-',
            'expectExact' => false,
            'id' => '   ',
        ];
        yield 'non prefixed id is normalized' => [
            'expectedPrefixOrValue' => 'OBL-C-200',
            'expectExact' => true,
            'id' => 'C-200',
        ];
        yield 'prefixed id is preserved' => [
            'expectedPrefixOrValue' => 'OBL-C-200',
            'expectExact' => true,
            'id' => 'OBL-C-200',
        ];
    }

    /**
     * @return iterable<string, array{id: string, expected: string}>
     */
    public static function updateIdNormalizationProvider(): iterable
    {
        yield 'non prefixed id is normalized' => [
            'expected' => 'OBL-C-400',
            'id' => 'C-400',
        ];
        yield 'prefixed id is preserved' => [
            'expected' => 'OBL-C-400',
            'id' => 'OBL-C-400',
        ];
    }

    /**
     * @return iterable<string, array{id: mixed}>
     */
    public static function invalidUpdateIdProvider(): iterable
    {
        yield 'missing id' => [ 'id' => null ];
        yield 'empty id' => [ 'id' => '' ];
        yield 'whitespace id' => [ 'id' => '  ' ];
        yield 'non string id' => [ 'id' => 123 ];
    }

    /**
     * @return iterable<string, list<string|null>>
     */
    public static function forcedIdentDescriptionProvider(): iterable
    {
        yield 'null caller value' => [ null ];
        yield 'explicit caller value' => [ 'Caller value' ];
    }

    #[DataProvider('createIdNormalizationProvider')]
    public function testCreateIdNormalization(string $expectedPrefixOrValue, bool $expectExact, mixed $id): void
    {
        $factory = new ContactRequestFactory();

        $payload = $this->validCreatePayload();
        $payload['id'] = $id;

        if (null === $id) {
            unset($payload['id']);
        }

        $request = $factory->createFromArray($payload);

        if ($expectExact) {
            self::assertSame($expectedPrefixOrValue, $request->id);

            return;
        }

        self::assertStringStartsWith($expectedPrefixOrValue, $request->id);
    }

    #[DataProvider('updateIdNormalizationProvider')]
    public function testUpdateIdNormalization(string $expected, string $id): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->updateFromArray([
            'email' => 'updated@example.rs',
            'id' => $id,
        ]);

        self::assertSame($expected, $request->id);
    }

    #[DataProvider('invalidUpdateIdProvider')]
    public function testUpdateRejectsInvalidIdsWithDeterministicError(mixed $id): void
    {
        $factory = new ContactRequestFactory();
        $payload = [ 'email' => 'updated@example.rs', 'id' => $id ];

        if (null === $id) {
            unset($payload['id']);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact update request key "id" must be a non-empty string.');

        $factory->updateFromArray($payload);
    }

    #[DataProvider('forcedIdentDescriptionProvider')]
    public function testCreateExtensionIdentDescriptionIsForcedToPolicyValue(?string $callerValue): void
    {
        $factory = new ContactRequestFactory();
        $payload = $this->validCreatePayload();
        $payload['extension'] = [ 'identDescription' => $callerValue ];

        $request = $factory->createFromArray($payload);

        self::assertNotNull($request->extension);
        self::assertSame(
            ContactRequestFactory::ENFORCED_IDENT_DESCRIPTION,
            $request->extension->identDescription,
        );
    }

    #[DataProvider('forcedIdentDescriptionProvider')]
    public function testUpdateExtensionIdentDescriptionIsForcedToPolicyValue(?string $callerValue): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->updateFromArray([
            'email' => 'updated@example.rs',
            'extension' => [
                'identDescription' => $callerValue,
            ],
            'id' => 'C-400',
        ]);

        self::assertNotNull($request->extension);
        self::assertSame(
            ContactRequestFactory::ENFORCED_IDENT_DESCRIPTION,
            $request->extension->identDescription,
        );
    }

    public function testCreateAllowsEmptyNameForLegalEntityWithOrganization(): void
    {
        $factory = new ContactRequestFactory();
        $payload = $this->validCreatePayload();
        $payload['postalInfo']['name'] = '';
        $payload['postalInfo']['organization'] = 'RNIDS Test Company';
        $payload['extension'] = [
            'isLegalEntity' => '1',
        ];

        $request = $factory->createFromArray($payload);

        self::assertSame('', $request->postalInfo->name);
        self::assertSame('RNIDS Test Company', $request->postalInfo->organization);
        self::assertSame('1', $request->extension?->isLegalEntity);
    }

    public function testCreateAllowsNonEmptyNameForLegalEntity(): void
    {
        $factory = new ContactRequestFactory();
        $payload = $this->validCreatePayload();
        $payload['postalInfo']['name'] = 'Legal Contact Person';
        $payload['extension'] = [
            'isLegalEntity' => '1',
        ];

        $request = $factory->createFromArray($payload);

        self::assertSame('Legal Contact Person', $request->postalInfo->name);
        self::assertSame('1', $request->extension?->isLegalEntity);
    }

    public function testCreateRejectsEmptyNameForLegalEntityWithoutOrganization(): void
    {
        $factory = new ContactRequestFactory();
        $payload = $this->validCreatePayload();
        $payload['postalInfo']['name'] = '';
        $payload['extension'] = [
            'isLegalEntity' => '1',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact postalInfo key "name" must be a non-empty string.');

        $factory->createFromArray($payload);
    }

    public function testCreateRejectsEmptyNameForNonLegalEntityEvenWithOrganization(): void
    {
        $factory = new ContactRequestFactory();
        $payload = $this->validCreatePayload();
        $payload['postalInfo']['name'] = '';
        $payload['postalInfo']['organization'] = 'RNIDS Test Company';
        $payload['extension'] = [
            'isLegalEntity' => '0',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact postalInfo key "name" must be a non-empty string.');

        $factory->createFromArray($payload);
    }

    /**
     * @return array{
     *   email: string,
     *   id: string,
     *   postalInfo: array{
     *     address: array{city: string, countryCode: string, streets: list<string>},
     *     name: string
     *   }
     * }
     */
    private function validCreatePayload(): array
    {
        return [
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
        ];
    }
}
