<?php

declare(strict_types=1);

namespace RNIDS\Xml\ClTrid;

/**
 * Generates client transaction IDs for EPP commands.
 */
interface ClTridGenerator
{
    /**
     * Returns the next client transaction identifier.
     */
    public function nextId(): string;
}
