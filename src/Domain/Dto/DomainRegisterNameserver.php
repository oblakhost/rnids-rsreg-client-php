<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents a nameserver definition used during domain register command.
 */
final class DomainRegisterNameserver
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @var list<DomainNameserverAddress>
     */
    public readonly array $addresses;

    /**
     * @param non-empty-string $name
     * @param list<DomainNameserverAddress|string> $addresses
     */
    public function __construct(
        string $name,
        array $addresses = [],
    ) {
        $this->name = $name;
        $this->addresses = \array_values(\array_map(
            fn(DomainNameserverAddress|string $address): DomainNameserverAddress => $this->normalizeAddress(
                $address,
            ),
            $addresses,
        ));
    }

    private function normalizeAddress(DomainNameserverAddress|string $address): DomainNameserverAddress
    {
        if ($address instanceof DomainNameserverAddress) {
            return $address;
        }

        return new DomainNameserverAddress($address, $this->inferIpVersion($address));
    }

    /**
     * @return 'v4'|'v6'
     */
    private function inferIpVersion(string $address): string
    {
        if (false !== \filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'v6';
        }

        return 'v4';
    }
}
