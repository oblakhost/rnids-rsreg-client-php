<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\TestCase;
use RNIDS\Session\Dto\LoginRequest;
use RNIDS\Xml\Session\LoginRequestBuilder;

/**
 * Unit tests for login request XML generation.
 */
final class LoginRequestBuilderTest extends TestCase
{
    /**
     * Verifies generated login XML includes escaped credentials and services.
     */
    public function testBuildCreatesLoginEnvelopeWithEscapedValuesAndServices(): void
    {
        $builder = new LoginRequestBuilder();

        $xml = $builder->build(
            new LoginRequest(
                clientId: 'client<&>',
                password: 'pass<&>',
                version: '1.0',
                language: 'en',
                objectUris: [ 'urn:ietf:params:xml:ns:domain-1.0' ],
                extensionUris: [ 'http://www.rnids.rs/rnids-epp/rnids-1.0' ],
            ),
            'TRID<&>',
        );

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">', $xml);
        self::assertStringContainsString('<login>', $xml);
        self::assertStringContainsString('<clID>client&lt;&amp;&gt;</clID>', $xml);
        self::assertStringContainsString('<pw>pass&lt;&amp;&gt;</pw>', $xml);
        self::assertStringContainsString('<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>', $xml);
        self::assertStringContainsString('<extURI>http://www.rnids.rs/rnids-epp/rnids-1.0</extURI>', $xml);
        self::assertStringContainsString('<clTRID>TRID&lt;&amp;&gt;</clTRID>', $xml);
        self::assertStringContainsString('</command></epp>', $xml);
    }

    /**
     * Verifies services block is omitted when URIs are not provided.
     */
    public function testBuildOmitsServicesWhenUrisAreNotProvided(): void
    {
        $builder = new LoginRequestBuilder();

        $xml = $builder->build(
            new LoginRequest(
                clientId: 'client',
                password: 'pass',
            ),
            'TRID-1',
        );

        self::assertStringNotContainsString('<svcs>', $xml);
        self::assertStringContainsString('<clTRID>TRID-1</clTRID>', $xml);
    }
}
