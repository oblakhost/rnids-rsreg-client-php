<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\TestCase;
use RNIDS\Xml\Session\LogoutRequestBuilder;

final class LogoutRequestBuilderTest extends TestCase
{
    public function testBuildCreatesLogoutEnvelopeWithEscapedClTrid(): void
    {
        $builder = new LogoutRequestBuilder();

        $xml = $builder->build('TRID<&>');

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">', $xml);
        self::assertStringContainsString(
            '<command><logout/><clTRID>TRID&lt;&amp;&gt;</clTRID></command>',
            $xml,
        );
        self::assertStringContainsString('</epp>', $xml);
    }
}
