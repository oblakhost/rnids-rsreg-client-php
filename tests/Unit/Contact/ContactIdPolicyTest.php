<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactIdPolicy;

#[Group('unit')]
final class ContactIdPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{input: mixed, expectedPrefixOrValue: string, expectExact: bool}>
     */
    public static function createNormalizationProvider(): iterable
    {
        yield 'missing id generates policy id' => [
            'expectedPrefixOrValue' => ContactIdPolicy::PREFIX,
            'expectExact' => false,
            'input' => null,
        ];
        yield 'whitespace id generates policy id' => [
            'expectedPrefixOrValue' => ContactIdPolicy::PREFIX,
            'expectExact' => false,
            'input' => '   ',
        ];
        yield 'non prefixed id is normalized' => [
            'expectedPrefixOrValue' => 'OBL-C-10',
            'expectExact' => true,
            'input' => 'C-10',
        ];
        yield 'prefixed id is preserved' => [
            'expectedPrefixOrValue' => 'OBL-C-10',
            'expectExact' => true,
            'input' => 'OBL-C-10',
        ];
    }

    /**
     * @return iterable<string, array{input: string, expected: string}>
     */
    public static function updateNormalizationProvider(): iterable
    {
        yield 'non prefixed id is normalized' => [
            'expected' => 'OBL-C-20',
            'input' => 'C-20',
        ];
        yield 'prefixed id is preserved' => [
            'expected' => 'OBL-C-20',
            'input' => 'OBL-C-20',
        ];
    }

    /**
     * @return iterable<string, array{input: mixed}>
     */
    public static function invalidUpdateIdProvider(): iterable
    {
        yield 'missing id' => [ 'input' => null ];
        yield 'empty id' => [ 'input' => '' ];
        yield 'whitespace id' => [ 'input' => '  ' ];
        yield 'non string id' => [ 'input' => 100 ];
    }

    #[DataProvider('createNormalizationProvider')]
    public function testNormalizeForCreate(string $expectedPrefixOrValue, bool $expectExact, mixed $input): void
    {
        $policy = new ContactIdPolicy();

        $normalized = $policy->normalizeForCreate($input);

        if ($expectExact) {
            self::assertSame($expectedPrefixOrValue, $normalized);

            return;
        }

        self::assertStringStartsWith($expectedPrefixOrValue, $normalized);
    }

    public function testNormalizeForCreateRejectsNonStringTypeWithDeterministicMessage(): void
    {
        $policy = new ContactIdPolicy();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact create request key "id" must be a string when provided.');

        $policy->normalizeForCreate(42);
    }

    #[DataProvider('updateNormalizationProvider')]
    public function testNormalizeForUpdate(string $expected, string $input): void
    {
        $policy = new ContactIdPolicy();

        self::assertSame($expected, $policy->normalizeForUpdate($input));
    }

    #[DataProvider('invalidUpdateIdProvider')]
    public function testNormalizeForUpdateRejectsInvalidIds(mixed $input): void
    {
        $policy = new ContactIdPolicy();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact update request key "id" must be a non-empty string.');

        $policy->normalizeForUpdate($input);
    }
}
