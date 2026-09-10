<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainNameserverAddress;
use RNIDS\Domain\Dto\DomainRegisterNameserver;
use RNIDS\Xml\XmlComposer;

final class DomainNameserverXmlBuilder
{
    /** @param list<DomainRegisterNameserver> $nameservers */
    public function build(array $nameservers): string
    {
        if ([] === $nameservers) {
            return '';
        }

        $useAttributes = [] !== \array_filter(
            $nameservers,
            static fn(DomainRegisterNameserver $nameserver): bool => [] !== $nameserver->addresses,
        );

        return '<domain:ns>'
            . \implode(
                '',
                \array_map(
                    fn(DomainRegisterNameserver $nameserver): string => $this->nameserverXml(
                        $nameserver,
                        $useAttributes,
                    ),
                    $nameservers,
                ),
            )
            . '</domain:ns>';
    }

    private function nameserverXml(DomainRegisterNameserver $nameserver, bool $useAttributes): string
    {
        if (!$useAttributes) {
            return XmlComposer::element('domain:hostObj', $nameserver->name);
        }

        return '<domain:hostAttr>'
            . XmlComposer::element('domain:hostName', $nameserver->name)
            . \implode(
                '',
                \array_map(
                    static fn(DomainNameserverAddress $address): string => '<domain:hostAddr ip="'
                        . XmlComposer::escape($address->ipVersion)
                        . '">'
                        . XmlComposer::escape($address->address)
                        . '</domain:hostAddr>',
                    $nameserver->addresses,
                ),
            )
            . '</domain:hostAttr>';
    }
}
