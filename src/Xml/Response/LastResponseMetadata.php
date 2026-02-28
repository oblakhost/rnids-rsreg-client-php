<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

/**
 * Holds the latest parsed EPP response metadata for the active client lifecycle.
 */
final class LastResponseMetadata
{
    private ?ResponseMetadata $metadata = null;

    /**
     * Stores metadata from the most recently parsed EPP response.
     */
    public function set(ResponseMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns metadata from the last parsed EPP response, when available.
     */
    public function get(): ?ResponseMetadata
    {
        return $this->metadata;
    }
}
