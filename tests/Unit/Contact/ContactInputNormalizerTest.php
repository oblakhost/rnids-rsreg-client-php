<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactInputNormalizer;

#[Group('unit')]
final class ContactInputNormalizerTest extends TestCase
{
    public function testNormalizeCheckRequestAcceptsSingleIdString(): void
    {
        $normalizer = new ContactInputNormalizer();

        self::assertSame([ 'ids' => [ 'C-100' ] ], $normalizer->normalizeCheckRequest('C-100'));
    }

    public function testNormalizeCheckRequestRejectsInvalidListElement(): void
    {
        $normalizer = new ContactInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact check request list must contain only non-empty strings.');

        $normalizer->normalizeCheckRequest([ 'C-100', '' ]);
    }

    public function testRequireContactIdRejectsEmptyValue(): void
    {
        $normalizer = new ContactInputNormalizer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact id must be a non-empty string.');

        $normalizer->requireContactId(' ');
    }
}
