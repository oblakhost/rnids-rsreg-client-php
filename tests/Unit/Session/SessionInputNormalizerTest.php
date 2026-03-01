<?php

declare(strict_types=1);

namespace Tests\Unit\Session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Session\SessionInputNormalizer;

#[Group('unit')]
final class SessionInputNormalizerTest extends TestCase
{
    public function testBuildLoginRequestUsesDefaultsAndLists(): void
    {
        $normalizer = new SessionInputNormalizer();

        $request = $normalizer->buildLoginRequest([
            'clientId' => 'cid',
            'objectUris' => [ 'urn:ietf:params:xml:ns:domain-1.0' ],
            'password' => 'secret',
        ]);

        self::assertSame('cid', $request->clientId);
        self::assertSame('secret', $request->password);
        self::assertSame('1.0', $request->version);
        self::assertSame('en', $request->language);
        self::assertSame([ 'urn:ietf:params:xml:ns:domain-1.0' ], $request->objectUris);
    }

    public function testBuildLoginRequestRejectsInvalidStringListItem(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Session request key "objectUris" must contain only non-empty strings.',
        );

        $normalizer->buildLoginRequest([
            'clientId' => 'cid',
            'objectUris' => [ '' ],
            'password' => 'secret',
        ]);
    }

    public function testBuildPollRequestRequiresMessageIdForAck(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "messageId" must be a non-empty string.');

        $normalizer->buildPollRequest([ 'operation' => 'ack' ]);
    }
}
