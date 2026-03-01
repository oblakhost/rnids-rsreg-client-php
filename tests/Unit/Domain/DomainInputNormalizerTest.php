<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainInputNormalizer;
use RNIDS\Domain\DomainNameserverNormalizer;

#[Group('unit')]
final class DomainInputNormalizerTest extends TestCase
{
    public function testNormalizeCheckNamesAcceptsSingleString(): void
    {
        $normalizer = new DomainInputNormalizer(new DomainNameserverNormalizer());

        self::assertSame([ 'example.rs' ], $normalizer->normalizeCheckNames('example.rs'));
    }

    public function testOptionalHostsRejectsInvalidValue(): void
    {
        $normalizer = new DomainInputNormalizer(new DomainNameserverNormalizer());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain info request key "hosts" must be one of "all", "del", "sub", or "none".',
        );

        $normalizer->optionalHosts('invalid');
    }

    public function testNormalizeRenewRequestSimplifiedUsesResolverAndNormalizesDate(): void
    {
        $normalizer = new DomainInputNormalizer(new DomainNameserverNormalizer());

        $result = $normalizer->normalizeRenewRequest(
            'example.rs',
            2,
            static fn(string $name): string => '2026-02-01T14:00:00Z',
        );

        self::assertSame('example.rs', $result['name']);
        self::assertSame('2026-02-01', $result['currentExpirationDate']);
        self::assertSame(2, $result['period']);
        self::assertSame('y', $result['periodUnit']);
    }

    public function testNormalizeRenewRequestSimplifiedRejectsNonPositiveYears(): void
    {
        $normalizer = new DomainInputNormalizer(new DomainNameserverNormalizer());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain renew years must be a positive integer when using simplified renew API.',
        );

        $normalizer->normalizeRenewRequest('example.rs', 0, static fn(string $name): string => '2026-02-01');
    }
}
