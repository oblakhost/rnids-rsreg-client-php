<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

final class LiveCleanup
{
    /** @var list<array{resource: string, remove: callable(): void}> */
    private array $resources = [];

    /** @param callable(): void $remove */
    public function add(string $resource, callable $remove): void
    {
        $this->resources[] = [ 'resource' => $resource, 'remove' => $remove ];
    }

    public function run(): void
    {
        $failures = [];
        foreach (\array_reverse($this->resources) as $resource) {
            try {
                ($resource['remove'])();
            } catch (\Throwable) {
                $failures[] = $resource['resource'];
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
}
