<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Xml\NamespaceRegistry;

/**
 * Builds XML payloads for EPP session hello commands.
 */
final class HelloRequestBuilder
{
    /**
     * Builds deterministic EPP hello XML.
     */
    public function build(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="' . NamespaceRegistry::EPP . '">'
            . '<hello/>'
            . '</epp>';
    }
}
