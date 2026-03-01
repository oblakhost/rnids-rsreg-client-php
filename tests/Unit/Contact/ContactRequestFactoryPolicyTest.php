<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactRequestFactory;

#[Group('unit')]
final class ContactRequestFactoryPolicyTest extends TestCase
{
    public function testCreateGeneratesOblPrefixedIdWhenMissing(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->createFromArray([
            'email' => 'person@example.rs',
            'postalInfo' => [
                'address' => [
                    'city' => 'Belgrade',
                    'countryCode' => 'RS',
                    'streets' => [ 'Main 1' ],
                ],
                'name' => 'Person Example',
            ],
        ]);

        self::assertStringStartsWith('OBL-', $request->id);
    }

    public function testCreateNormalizesIdPrefixWhenMissingOblPrefix(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->createFromArray([
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

        self::assertSame('OBL-C-200', $request->id);
    }

    public function testCreateKeepsOblPrefixedIdUntouched(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->createFromArray([
            'email' => 'person@example.rs',
            'id' => 'OBL-C-200',
            'postalInfo' => [
                'address' => [
                    'city' => 'Belgrade',
                    'countryCode' => 'RS',
                    'streets' => [ 'Main 1' ],
                ],
                'name' => 'Person Example',
            ],
        ]);

        self::assertSame('OBL-C-200', $request->id);
    }

    public function testUpdateNormalizesIdPrefixWhenMissingOblPrefix(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->updateFromArray([
            'email' => 'updated@example.rs',
            'id' => 'C-400',
        ]);

        self::assertSame('OBL-C-400', $request->id);
    }

    public function testUpdateWithoutIdStillFailsWithDeterministicError(): void
    {
        $factory = new ContactRequestFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact update request key "id" must be a non-empty string.');

        $factory->updateFromArray([
            'email' => 'updated@example.rs',
        ]);
    }

    public function testCreateExtensionIdentDescriptionIsForcedToPolicyValue(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->createFromArray([
            'email' => 'person@example.rs',
            'extension' => [
                'identDescription' => 'Caller value',
            ],
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

        self::assertNotNull($request->extension);
        self::assertSame(
            'Object Creation provided by Oblak Solutions.',
            $request->extension->identDescription,
        );
    }

    public function testUpdateExtensionIdentDescriptionIsForcedToPolicyValue(): void
    {
        $factory = new ContactRequestFactory();

        $request = $factory->updateFromArray([
            'email' => 'updated@example.rs',
            'extension' => [
                'identDescription' => 'Caller value',
            ],
            'id' => 'C-400',
        ]);

        self::assertNotNull($request->extension);
        self::assertSame(
            'Object Creation provided by Oblak Solutions.',
            $request->extension->identDescription,
        );
    }
}
