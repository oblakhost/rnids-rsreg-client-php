<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain info commands.
 */
final class DomainInfoRequestBuilder
{
    /**
     * Builds a deterministic EPP domain info XML command.
     */
    public function build(DomainInfoRequest $request, string $clTrid): string
    {
        $xml = '<info>'
            . '<domain:info xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . '<domain:name hosts="' . XmlComposer::escape($request->hosts) . '">'
            . XmlComposer::escape($request->name)
            . '</domain:name>'
            . '</domain:info>'
            . '</info>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
