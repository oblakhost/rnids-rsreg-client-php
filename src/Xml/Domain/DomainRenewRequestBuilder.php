<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRenewRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain renew commands.
 */
final class DomainRenewRequestBuilder
{
    /**
     * Builds a deterministic EPP domain renew XML command.
     */
    public function build(DomainRenewRequest $request, string $clTrid): string
    {
        $xml = '<renew>'
            . '<domain:renew xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . XmlComposer::element('domain:curExpDate', $request->currentExpirationDate)
            . $this->periodXml($request)
            . '</domain:renew>'
            . '</renew>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function periodXml(DomainRenewRequest $request): string
    {
        if (null === $request->period) {
            return '';
        }

        return '<domain:period unit="' . XmlComposer::escape($request->periodUnit) . '">'
            . (string) $request->period
            . '</domain:period>';
    }
}
