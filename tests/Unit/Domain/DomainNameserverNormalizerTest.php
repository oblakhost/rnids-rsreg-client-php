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
