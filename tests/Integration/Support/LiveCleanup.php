<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

final class LiveCleanup
{
    /** @var list<array{resource: string, remove: callable(): (string|null)}> */
    private array $resources = [];

    /** @param callable(): (string|null) $remove */
    public function add(string $resource, callable $remove): void
    {
        $this->resources[] = [ 'resource' => $resource, 'remove' => $remove ];
        $this->record($resource, 'created');
    }

    public function run(): void
    {
        $failures = [];
        foreach (\array_reverse($this->resources) as $resource) {
            try {
                $state = ($resource['remove'])();
                $this->record($resource['resource'], 'pending' === $state ? 'pending' : 'removed');
            } catch (\Throwable) {
                $failures[] = $resource['resource'];
                $this->record($resource['resource'], 'failed');
            }
        }
        $this->resources = [];
        if ([] !== $failures) {
            throw new \RuntimeException(
                'Live cleanup failed for: ' . \implode(', ', $failures)
                . '. Remove these test resources manually.',
            );
        }
    }

    private function record(string $resource, string $state): void
    {
        $configured = \getenv('RNIDS_EPP_RESOURCE_LEDGER');
        $path = \is_string($configured) && '' !== $configured
            ? $configured
            : \sys_get_temp_dir() . '/rnids-live-resources-' . \getmypid() . '.json';
        $records = \is_file($path)
            ? \json_decode((string) \file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)
            : [];
        $records[$resource] = $state;
        if (false === \file_put_contents($path, \json_encode($records, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX)) {
            throw new \RuntimeException('Could not persist the live resource cleanup ledger.');
        }
    }
}
