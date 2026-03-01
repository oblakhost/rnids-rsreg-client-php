<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRegisterExtension;
use RNIDS\Domain\Dto\DomainUpdateRequest;
use RNIDS\Domain\Dto\DomainUpdateSection;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain update commands.
 */
final class DomainUpdateRequestBuilder
{
    /**
     * Builds a deterministic EPP domain update XML command.
     */
    public function build(DomainUpdateRequest $request, string $clTrid): string
    {
        $xml = '<update>'
            . '<domain:update xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . $this->sectionXml('domain:add', $request->add)
            . $this->sectionXml('domain:rem', $request->remove)
            . $this->changeXml($request)
            . '</domain:update>'
            . '</update>'
            . $this->extensionXml($request->extension);

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function sectionXml(string $nodeName, ?DomainUpdateSection $section): string
    {
        if (null === $section) {
            return '';
        }

        return '<' . $nodeName . '>'
            . $this->contactsXml($section)
            . $this->statusesXml($section)
            . '</' . $nodeName . '>';
    }

    private function contactsXml(DomainUpdateSection $section): string
    {
        if ([] === $section->contacts) {
            return '';
        }

        return \implode(
            '',
            \array_map(
                static fn(DomainRegisterContact $contact): string => '<domain:contact type="'
                    . XmlComposer::escape($contact->type)
                    . '">'
                    . XmlComposer::escape($contact->handle)
                    . '</domain:contact>',
                $section->contacts,
            ),
        );
    }

    private function statusesXml(DomainUpdateSection $section): string
    {
        if ([] === $section->statuses) {
            return '';
        }

        return \implode(
            '',
            \array_map(
                static fn(string $status): string => '<domain:status s="'
                    . XmlComposer::escape($status)
                    . '"/>',
                $section->statuses,
            ),
        );
    }

    private function changeXml(DomainUpdateRequest $request): string
    {
        if (null === $request->registrant && null === $request->authInfo) {
            return '';
        }

        return '<domain:chg>'
            . (null !== $request->registrant ? XmlComposer::element(
                'domain:registrant',
                $request->registrant,
            ) : '')
            . (null !== $request->authInfo
                ? '<domain:authInfo>' . XmlComposer::element(
                    'domain:pw',
                    $request->authInfo,
                ) . '</domain:authInfo>'
                : '')
            . '</domain:chg>';
    }

    private function extensionXml(?DomainRegisterExtension $extension): string
    {
        if (null === $extension) {
            return '';
        }

        $parts = \array_values(\array_filter([
            $this->extensionRemarkXml($extension->remark),
            $this->extensionBoolXml('domainExt:isWhoisPrivacy', $extension->isWhoisPrivacy),
            $this->extensionOperationModeXml($extension->operationMode),
            $this->extensionBoolXml('domainExt:notifyAdmin', $extension->notifyAdmin),
            $this->extensionBoolXml('domainExt:dnsSec', $extension->dnsSec),
        ]));

        if ([] === $parts) {
            return '';
        }

        return '<extension>'
            . '<domainExt:domain-ext xmlns:domainExt="' . NamespaceRegistry::RNIDS_DOMAIN_EXT . '">'
            . \implode('', $parts)
            . '</domainExt:domain-ext>'
            . '</extension>';
    }

    private function extensionRemarkXml(?string $remark): ?string
    {
        return null !== $remark ? XmlComposer::element('domainExt:remark', $remark) : null;
    }

    private function extensionOperationModeXml(?string $operationMode): ?string
    {
        return null !== $operationMode ? XmlComposer::element(
            'domainExt:operationMode',
            $operationMode,
        ) : null;
    }

    private function extensionBoolXml(string $nodeName, ?bool $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return XmlComposer::element($nodeName, $value ? 'true' : 'false');
    }
}
