<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainDnssecCreate;
use RNIDS\Domain\Dto\DomainDnssecUpdate;
use RNIDS\Domain\Dto\DomainDsRecord;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

final class DomainDnssecXmlBuilder
{
    public function create(?DomainDnssecCreate $dnssec): string
    {
        return null === $dnssec ? '' : '<secDNS:create xmlns:secDNS="' . NamespaceRegistry::SECDNS . '">'
            . $this->records($dnssec->records) . '</secDNS:create>';
    }

    public function update(?DomainDnssecUpdate $dnssec): string
    {
        if (null === $dnssec) {
            return '';
        }

        $remove = $dnssec->removeAll ? '<secDNS:all>true</secDNS:all>' : $this->records($dnssec->remove);
        $add = $this->records($dnssec->add);

        return '<secDNS:update xmlns:secDNS="' . NamespaceRegistry::SECDNS . '">'
            . ('' === $remove ? '' : '<secDNS:rem>' . $remove . '</secDNS:rem>')
            . ('' === $add ? '' : '<secDNS:add>' . $add . '</secDNS:add>')
            . '</secDNS:update>';
    }

    /** @param list<DomainDsRecord> $records */
    private function records(array $records): string
    {
        return \implode('', \array_map(static fn(DomainDsRecord $record): string => '<secDNS:dsData>'
            . XmlComposer::element('secDNS:keyTag', (string) $record->keyTag)
            . XmlComposer::element('secDNS:alg', (string) $record->alg)
            . XmlComposer::element('secDNS:digestType', (string) $record->digestType)
            . XmlComposer::element('secDNS:digest', $record->digest)
            . '</secDNS:dsData>', $records));
    }
}
