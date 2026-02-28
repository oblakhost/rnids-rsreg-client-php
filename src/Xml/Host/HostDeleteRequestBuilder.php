<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostDeleteRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP host delete commands.
 */
final class HostDeleteRequestBuilder
{
    public function build(HostDeleteRequest $request, string $clTrid): string
    {
        $xml = '<delete>'
            . '<host:delete xmlns:host="' . NamespaceRegistry::HOST . '">'
            . XmlComposer::element('host:name', $request->name)
            . '</host:delete>'
            . '</delete>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
