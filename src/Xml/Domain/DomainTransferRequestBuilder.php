<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainTransferRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain transfer commands.
 */
final class DomainTransferRequestBuilder
{
    /**
     * Builds a deterministic EPP domain transfer XML command.
     */
    public function build(DomainTransferRequest $request, string $clTrid): string
    {
        $xml = '<transfer op="' . XmlComposer::escape($request->operation) . '">'
            . '<domain:transfer xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . $this->periodXml($request)
            . $this->authInfoXml($request)
            . '</domain:transfer>'
            . '</transfer>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function periodXml(DomainTransferRequest $request): string
    {
        if (null === $request->period) {
            return '';
        }

        return '<domain:period unit="' . XmlComposer::escape($request->periodUnit) . '">'
            . (string) $request->period
            . '</domain:period>';
    }

    private function authInfoXml(DomainTransferRequest $request): string
    {
        if (null === $request->authInfo) {
            return '';
        }

        return '<domain:authInfo>'
            . XmlComposer::element('domain:pw', $request->authInfo)
            . '</domain:authInfo>';
    }
}
