<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

/**
 * Holds the latest parsed EPP response metadata for the active client lifecycle.
 */
final class LastResponseMetadata
{
    private ?ResponseMetadata $metadata = null;

    public function set(ResponseMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function get(): ?ResponseMetadata
    {
        return $this->metadata;
    }
}
