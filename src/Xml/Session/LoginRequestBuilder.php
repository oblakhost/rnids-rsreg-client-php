<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\LoginRequest;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP session login commands.
 */
final class LoginRequestBuilder
{
    /**
     * Builds a deterministic EPP login command XML.
     */
    public function build(LoginRequest $request, string $clTrid): string
    {
        $xml = '<login>'
            . XmlComposer::element('clID', $request->clientId)
            . XmlComposer::element('pw', $request->password)
            . '<options>'
            . XmlComposer::element('version', $request->version)
            . XmlComposer::element('lang', $request->language)
            . '</options>'
            . $this->servicesFragment($request);

        return XmlComposer::commandEnvelope($xml . '</login>', $clTrid);
    }

    private function servicesFragment(LoginRequest $request): string
    {
        if ([] === $request->objectUris) {
            return '';
        }

        $serviceExtension = [] !== $request->extensionUris
            ? '<svcExtension>' . $this->uriElements('extURI', $request->extensionUris) . '</svcExtension>'
            : '';

        return '<svcs>'
            . $this->uriElements('objURI', $request->objectUris)
            . $serviceExtension
            . '</svcs>';
    }

    /**
     * @param list<string> $uris
     */
    private function uriElements(string $elementName, array $uris): string
    {
        return \implode(
            '',
            \array_map(
                static fn(string $uri): string => XmlComposer::element($elementName, $uri),
                $uris,
            ),
        );
    }
}
