<?php

declare(strict_types=1);

namespace RNIDS\Xml\ClTrid;

/**
 * Deterministic incremental generator for client transaction IDs.
 */
final class IncrementalClTridGenerator implements ClTridGenerator
{
    private int $counter;

    /**
     * @param string $prefix Prefix used in generated identifiers.
     * @param int $startAt Initial numeric counter value.
     */
    public function __construct(
        private readonly string $prefix = 'RNIDS',
        int $startAt = 1,
    ) {
        $this->counter = $startAt;
    }

    /**
     * Returns the next identifier and increments internal counter.
     */
    public function nextId(): string
    {
        $current = $this->counter;
        ++$this->counter;

        return \sprintf('%s-%08d', $this->prefix, $current);
    }
}
