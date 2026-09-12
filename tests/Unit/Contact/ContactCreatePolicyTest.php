<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactService;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use Tests\Support\ContactFixtureFactory;
use Tests\Support\SessionPeerTransport;

final class ContactCreatePolicyTest extends TestCase
{
    /** @return iterable<string, array{voiceInput: array{voice?: string|null}}> */
    public static function missingVoiceInputs(): iterable
    {
        yield 'omitted' => ['voiceInput' => []];
        yield 'null' => ['voiceInput' => ['voice' => null]];
        yield 'empty' => ['voiceInput' => ['voice' => '']];
    }

    /** @param array{voice?: string|null} $voiceInput */
    #[DataProvider('missingVoiceInputs')]
    public function testCreateWithoutVoiceIsRejectedBeforeSendingACommand(array $voiceInput): void
    {
        $peer = new SessionPeerTransport(1000, false);
        $peer->connect();
        $peer->nextResponse = SessionPeerTransport::response(1000, 'CONTACT-CREATE-00000001');
        $service = new ContactService($peer, null, new IncrementalClTridGenerator('CONTACT-CREATE'));
        $payload = ContactFixtureFactory::forSeed('mandatory-voice')->individualCreatePayload();
        unset($payload['voice']);

        try {
            $service->create([...$payload, ...$voiceInput]);
            self::fail('Contact create without a phone was sent to the registry.');
        } catch (\InvalidArgumentException $error) {
            self::assertStringContainsString('"voice"', $error->getMessage());
            self::assertStringContainsString('non-empty', $error->getMessage());
        }

        self::assertSame([], $peer->requests);
    }
}
