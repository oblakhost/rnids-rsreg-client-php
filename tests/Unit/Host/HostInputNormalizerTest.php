<?php

declare(strict_types=1);

namespace Tests\Unit\Host;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\HostInputNormalizer;

#[Group('unit')]
final class HostInputNormalizerTest extends TestCase
{
    public function testNormalizeCheckRequestAcceptsSingleHostString(): void
    {
        $normalizer = new HostInputNormalizer();

        self::assertSame(
            [ 'names' => [ 'ns1.example.rs' ] ],
            $normalizer->normalizeCheckRequest('ns1.example.rs'),
        );
    }

    public function testNormalizeCreateRequestBuildsAddressesFromIpArgs(): void
    {
        $normalizer = new HostInputNormalizer();

        $result = $normalizer->normalizeCreateRequest('ns1.example.rs', '192.0.2.1', '2001:db8::1');

        self::assertSame('ns1.example.rs', $result['name']);
        self::assertCount(2, $result['addresses']);
        self::assertSame('v4', $result['addresses'][0]['ipVersion']);
        self::assertSame('v6', $result['addresses'][1]['ipVersion']);
    }

    public function testNormalizeCreateRequestRejectsBlankIpv4(): void
    {
        $normalizer = new HostInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host create ipv4 must be a non-empty string when provided.');

        $normalizer->normalizeCreateRequest('ns1.example.rs', '  ', null);
    }
}
