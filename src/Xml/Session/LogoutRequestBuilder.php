<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP session logout commands.
 */
final class LogoutRequestBuilder
{
    /**
     * Builds a deterministic EPP logout command XML.
     */
    public function build(string $clTrid): string
    {
        return XmlComposer::commandEnvelope('<logout/>', $clTrid);
    }
}
