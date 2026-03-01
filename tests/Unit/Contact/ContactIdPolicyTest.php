<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactIdPolicy;

#[Group('unit')]
final class ContactIdPolicyTest extends TestCase
{
    public function testNormalizeForCreateGeneratesIdWhenMissingOrEmpty(): void
    {
        $policy = new ContactIdPolicy();

        self::assertStringStartsWith(ContactIdPolicy::PREFIX, $policy->normalizeForCreate(null));
        self::assertStringStartsWith(ContactIdPolicy::PREFIX, $policy->normalizeForCreate('   '));
    }

    public function testNormalizeForCreatePrefixesNonPrefixedId(): void
    {
        $policy = new ContactIdPolicy();

        self::assertSame('OBL-C-10', $policy->normalizeForCreate('C-10'));
    }

    public function testNormalizeForCreateKeepsPrefixedId(): void
    {
        $policy = new ContactIdPolicy();

        self::assertSame('OBL-C-10', $policy->normalizeForCreate('OBL-C-10'));
    }

    public function testNormalizeForUpdateRequiresIdAndPrefixesWhenNeeded(): void
    {
        $policy = new ContactIdPolicy();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact update request key "id" must be a non-empty string.');

        $policy->normalizeForUpdate(' ');
    }

    public function testNormalizeForUpdateKeepsOrAddsPrefix(): void
    {
        $policy = new ContactIdPolicy();

        self::assertSame('OBL-C-20', $policy->normalizeForUpdate('C-20'));
        self::assertSame('OBL-C-20', $policy->normalizeForUpdate('OBL-C-20'));
    }
}
