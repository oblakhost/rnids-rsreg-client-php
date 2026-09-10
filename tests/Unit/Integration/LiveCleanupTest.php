<?php

declare(strict_types=1);

namespace Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\LiveCleanup;

final class LiveCleanupTest extends TestCase
{
    public function testCleanupRunsInReverseOrderAndReportsFailuresAfterTryingEverything(): void
    {
        $cleanup = new LiveCleanup();
        $deleted = [];
        $cleanup->add('contact C-1', static function () use (&$deleted): void {
            $deleted[] = 'contact';
        });
        $cleanup->add('domain test.rs', static function () use (&$deleted): void {
            $deleted[] = 'domain';
            throw new \RuntimeException('potentially sensitive server response');
        });
        try {
            $cleanup->run();
            self::fail('A cleanup error must fail the live test.');
        } catch (\RuntimeException $error) {
            self::assertSame(
                'Live cleanup failed for: domain test.rs. Remove these test resources manually.',
                $error->getMessage(),
            );
        }
        self::assertSame([ 'domain', 'contact' ], $deleted);
    }
}
