<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainDeleteRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain delete commands.
 */
final class DomainDeleteRequestBuilder
{
    /**
     * Builds a deterministic EPP domain delete XML command.
     */
    public function build(DomainDeleteRequest $request, string $clTrid): string
    {
        $xml = '<delete>'
            . '<domain:delete xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . '</domain:delete>'
            . '</delete>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
