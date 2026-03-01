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
        self::assertSame([], $request->extensionUris);
    }

    public function testBuildLoginRequestUsesProvidedVersionLanguageAndExtensionUris(): void
    {
        $normalizer = new SessionInputNormalizer();

        $request = $normalizer->buildLoginRequest([
            'clientId' => 'cid',
            'extensionUris' => [ 'http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0' ],
            'language' => 'sr',
            'password' => 'secret',
            'version' => '1.1',
        ]);

        self::assertSame('1.1', $request->version);
        self::assertSame('sr', $request->language);
        self::assertSame([ 'http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0' ], $request->extensionUris);
    }

    public function testBuildLoginRequestRejectsMissingClientId(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "clientId" must be a non-empty string.');

        $normalizer->buildLoginRequest([
            'password' => 'secret',
        ]);
    }

    public function testBuildLoginRequestRejectsInvalidOptionalVersion(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "version" must be a non-empty string.');

        $normalizer->buildLoginRequest([
            'clientId' => 'cid',
            'password' => 'secret',
            'version' => '',
        ]);
    }

    public function testBuildLoginRequestRejectsNonListObjectUris(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "objectUris" must be a list of strings.');

        $normalizer->buildLoginRequest([
            'clientId' => 'cid',
            'objectUris' => 'urn:ietf:params:xml:ns:domain-1.0',
            'password' => 'secret',
        ]);
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

    public function testBuildPollRequestDefaultsToReqAndAllowsMissingMessageId(): void
    {
        $normalizer = new SessionInputNormalizer();

        $request = $normalizer->buildPollRequest([]);

        self::assertSame('req', $request->operation);
        self::assertNull($request->messageId);
    }

    public function testBuildPollRequestReqAllowsOptionalMessageId(): void
    {
        $normalizer = new SessionInputNormalizer();

        $request = $normalizer->buildPollRequest([
            'messageId' => '123',
            'operation' => 'req',
        ]);

        self::assertSame('req', $request->operation);
        self::assertSame('123', $request->messageId);
    }

    public function testBuildPollRequestRequiresMessageIdForAck(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "messageId" must be a non-empty string.');

        $normalizer->buildPollRequest([ 'operation' => 'ack' ]);
    }

    public function testBuildPollRequestRejectsInvalidOperation(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session poll request key "operation" must be either "req" or "ack".');

        $normalizer->buildPollRequest([ 'operation' => 'fetch' ]);
    }

    public function testBuildPollRequestRejectsInvalidOptionalMessageIdForReq(): void
    {
        $normalizer = new SessionInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session request key "messageId" must be a non-empty string when provided.');

        $normalizer->buildPollRequest([
            'messageId' => '',
            'operation' => 'req',
        ]);
    }
}
