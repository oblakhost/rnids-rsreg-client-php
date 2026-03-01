<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainNameserverNormalizer;

#[Group('unit')]
final class DomainNameserverNormalizerTest extends TestCase
{
    public function testNormalizeSimplifiedNameserversAcceptsString(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        self::assertSame(
            [ [ 'name' => 'ns1.example.rs' ] ],
            $normalizer->normalizeSimplifiedNameservers('ns1.example.rs'),
        );
    }

    public function testNormalizeSimplifiedNameserversRejectsMissingValue(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register simplified API requires at least one nameserver.');

        $normalizer->normalizeSimplifiedNameservers(null);
    }

    public function testNormalizeSimplifiedNameserversAcceptsStructuredList(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        self::assertSame(
            [
                [ 'name' => 'ns1.example.rs' ],
                [
                    'addresses' => [ '192.0.2.1', '2001:db8::1' ],
                    'name' => 'ns2.example.rs',
                ],
            ],
            $normalizer->normalizeSimplifiedNameservers([
                [ 'name' => 'ns1.example.rs' ],
                [ 'addresses' => [ '192.0.2.1', '2001:db8::1' ], 'name' => 'ns2.example.rs' ],
            ]),
        );
    }

    public function testNormalizeSimplifiedNameserversRejectsEmptyList(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register simplified API requires at least one nameserver.');

        $normalizer->normalizeSimplifiedNameservers([]);
    }

    public function testNormalizeSimplifiedNameserversRejectsInvalidNameserverType(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register nameserver at index 0 is invalid.');

        $normalizer->normalizeSimplifiedNameservers([ 123 ]);
    }

    public function testNormalizeSimplifiedNameserversRejectsBlankNameserverName(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register nameserver at index 0 requires non-empty "name".');

        $normalizer->normalizeSimplifiedNameservers([
            [ 'name' => '   ' ],
        ]);
    }

    public function testNormalizeSimplifiedNameserversRejectsInvalidAddressesListType(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register nameserver at index 0 field "addresses" must be a list.');

        $normalizer->normalizeSimplifiedNameservers([
            [ 'addresses' => 'bad', 'name' => 'ns1.example.rs' ],
        ]);
    }

    public function testNormalizeSimplifiedNameserversRejectsInvalidAddressItem(): void
    {
        $normalizer = new DomainNameserverNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register nameserver address at index 1 is invalid.');

        $normalizer->normalizeSimplifiedNameservers([
            [
                'addresses' => [ '192.0.2.1', '' ],
                'name' => 'ns1.example.rs',
            ],
        ]);
    }
}
