<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostInfoRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP host info commands.
 */
final class HostInfoRequestBuilder
{
    public function build(HostInfoRequest $request, string $clTrid): string
    {
        $xml = '<info>'
            . '<host:info xmlns:host="' . NamespaceRegistry::HOST . '">'
            . XmlComposer::element('host:name', $request->name)
            . '</host:info>'
            . '</info>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
