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

    public function testDeferredRegistryDeletionIsRecordedWithoutClaimingRemoval(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'rnids-cleanup-ledger-');
        \unlink($path);
        $previous = \getenv('RNIDS_EPP_RESOURCE_LEDGER');
        \putenv('RNIDS_EPP_RESOURCE_LEDGER=' . $path);
        try {
            $cleanup = new LiveCleanup();
            $cleanup->add('domain disposable.rs', static fn(): string => 'pending');
            $cleanup->run();
            self::assertSame(
                ['domain disposable.rs' => 'pending'],
                \json_decode((string) \file_get_contents($path), true, 512, JSON_THROW_ON_ERROR),
            );
        } finally {
            \unlink($path);
            \putenv(false === $previous ? 'RNIDS_EPP_RESOURCE_LEDGER' : 'RNIDS_EPP_RESOURCE_LEDGER=' . $previous);
        }
    }
}
