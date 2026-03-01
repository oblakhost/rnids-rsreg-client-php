<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainInputNormalizer;
use RNIDS\Domain\DomainNameserverNormalizer;

#[Group('unit')]
final class DomainInputNormalizerTest extends TestCase
{
    public function testNormalizeCheckNamesAcceptsSingleString(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame([ 'example.rs' ], $normalizer->normalizeCheckNames('example.rs'));
    }

    public function testNormalizeCheckNamesAcceptsNamedList(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame(
            [ 'example.rs', 'example.net.rs' ],
            $normalizer->normalizeCheckNames([ 'names' => [ 'example.rs', 'example.net.rs' ] ]),
        );
    }

    public function testNormalizeCheckNamesAcceptsPositionalList(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame(
            [ 'example.rs', 'example.net.rs' ],
            $normalizer->normalizeCheckNames([ 'example.rs', 'example.net.rs' ]),
        );
    }

    public function testNormalizeCheckNamesRejectsInvalidItem(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain check request key "names" must contain only non-empty strings.');

        $normalizer->normalizeCheckNames([ 'names' => [ 'example.rs', '' ] ]);
    }

    public function testOptionalHostsUsesAllByDefault(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame('all', $normalizer->optionalHosts(null));
    }

    #[DataProvider('validHostsProvider')]
    public function testOptionalHostsAcceptsAllowedValues(string $hosts): void
    {
        $normalizer = $this->normalizer();

        self::assertSame($hosts, $normalizer->optionalHosts($hosts));
    }

    /**
     * @return iterable<string, array{hosts: string}>
     */
    public static function validHostsProvider(): iterable
    {
        yield 'all' => [ 'hosts' => 'all' ];
        yield 'delegated' => [ 'hosts' => 'del' ];
        yield 'subordinate' => [ 'hosts' => 'sub' ];
        yield 'none' => [ 'hosts' => 'none' ];
    }

    public function testOptionalHostsRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain info request key "hosts" must be one of "all", "del", "sub", or "none".',
        );

        $normalizer->optionalHosts('invalid');
    }

    public function testRequireDomainNameRejectsBlankString(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain name must be a non-empty string.');

        $normalizer->requireDomainName('  ');
    }

    public function testRequireNameRejectsMissingValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain info request key "name" must be a non-empty string.');

        $normalizer->requireName([]);
    }

    public function testRequireCurrentExpirationDateRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain renew request key "currentExpirationDate" must be a non-empty string.',
        );

        $normalizer->requireCurrentExpirationDate([ 'currentExpirationDate' => '' ]);
    }

    public function testOptionalNullableStringSupportsNullAndValue(): void
    {
        $normalizer = $this->normalizer();

        self::assertNull($normalizer->optionalNullableString([], 'authInfo'));
        self::assertSame('secret', $normalizer->optionalNullableString([ 'authInfo' => 'secret' ], 'authInfo'));
    }

    public function testOptionalNullableStringRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain request key "authInfo" must be a non-empty string when provided.');

        $normalizer->optionalNullableString([ 'authInfo' => '' ], 'authInfo');
    }

    public function testOptionalPositiveIntSupportsNullAndValue(): void
    {
        $normalizer = $this->normalizer();

        self::assertNull($normalizer->optionalPositiveInt([], 'period'));
        self::assertSame(2, $normalizer->optionalPositiveInt([ 'period' => 2 ], 'period'));
    }

    public function testOptionalPositiveIntRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain request key "period" must be a positive integer when provided.');

        $normalizer->optionalPositiveInt([ 'period' => 0 ], 'period');
    }

    public function testOptionalPeriodUnitDefaultsToYears(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame('y', $normalizer->optionalPeriodUnit([]));
    }

    public function testOptionalPeriodUnitRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain request key "periodUnit" must be either "y" or "m" when provided.');

        $normalizer->optionalPeriodUnit([ 'periodUnit' => 'w' ]);
    }

    public function testRequireTransferOperationAcceptsAllowedValues(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame('request', $normalizer->requireTransferOperation([ 'operation' => 'request' ]));
        self::assertSame('approve', $normalizer->requireTransferOperation([ 'operation' => 'approve' ]));
    }

    public function testRequireTransferOperationRejectsInvalidValue(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain transfer request key "operation" must be one of "request", "query", "cancel", "approve", or "reject".',
        );

        $normalizer->requireTransferOperation([ 'operation' => 'deny' ]);
    }

    public function testNormalizeRegisterRequestSimplifiedBuildsFullPayload(): void
    {
        $normalizer = $this->normalizer();

        $result = $normalizer->normalizeRegisterRequest(
            'example.rs',
            'REG-1',
            'ADM-1',
            'TECH-1',
            [ 'ns1.example.rs', [ 'addresses' => [ '192.0.2.11' ], 'name' => 'ns2.example.rs' ] ],
            1,
            'domain-secret',
            [ 'operationMode' => 'secure' ],
        );

        self::assertSame('example.rs', $result['name']);
        self::assertSame('REG-1', $result['registrant']);
        self::assertSame(1, $result['period']);
        self::assertSame('y', $result['periodUnit']);
        self::assertCount(2, $result['contacts']);
        self::assertSame('admin', $result['contacts'][0]['type']);
        self::assertSame('TECH-1', $result['contacts'][1]['handle']);
        self::assertCount(2, $result['nameservers']);
        self::assertSame('domain-secret', $result['authInfo']);
        self::assertSame([ 'operationMode' => 'secure' ], $result['extension']);
    }

    public function testNormalizeRegisterRequestArrayIsReturnedAsIs(): void
    {
        $normalizer = $this->normalizer();
        $input = [ 'name' => 'example.rs', 'period' => 2 ];

        self::assertSame($input, $normalizer->normalizeRegisterRequest($input, null, null, null, null, null, null, null));
    }

    public function testNormalizeRegisterRequestSimplifiedRejectsInvalidYears(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register years must be a positive integer in simplified register API.');

        $normalizer->normalizeRegisterRequest('example.rs', 'REG-1', 'ADM-1', 'TECH-1', 'ns1.example.rs', 0, null, null);
    }

    public function testNormalizeRegisterRequestSimplifiedRejectsMissingRequiredFields(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain register key "adminContact" must be a non-empty string in simplified API.');

        $normalizer->normalizeRegisterRequest('example.rs', 'REG-1', null, 'TECH-1', 'ns1.example.rs', 1, null, null);
    }

    public function testNormalizeRenewRequestSimplifiedUsesResolverAndNormalizesDate(): void
    {
        $normalizer = $this->normalizer();

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

    public function testNormalizeRenewRequestArrayIsReturnedAsIs(): void
    {
        $normalizer = $this->normalizer();
        $input = [ 'currentExpirationDate' => '2027-01-01', 'name' => 'example.rs' ];

        self::assertSame($input, $normalizer->normalizeRenewRequest($input, null, static fn(string $name): string => 'unused'));
    }

    public function testNormalizeRenewRequestSimplifiedRejectsNonPositiveYears(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Domain renew years must be a positive integer when using simplified renew API.',
        );

        $normalizer->normalizeRenewRequest('example.rs', 0, static fn(string $name): string => '2026-02-01');
    }

    public function testNormalizeExpirationDateForRenewAcceptsDateOnly(): void
    {
        $normalizer = $this->normalizer();

        self::assertSame('2026-02-01', $normalizer->normalizeExpirationDateForRenew('2026-02-01'));
    }

    public function testNormalizeExpirationDateForRenewRejectsInvalidFormat(): void
    {
        $normalizer = $this->normalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resolved domain expiration date has invalid format for renew request.');

        $normalizer->normalizeExpirationDateForRenew('01.02.2026');
    }

    private function normalizer(): DomainInputNormalizer
    {
        return new DomainInputNormalizer(new DomainNameserverNormalizer());
    }
}
