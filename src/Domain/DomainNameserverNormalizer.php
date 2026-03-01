<?php

declare(strict_types=1);

namespace RNIDS\Domain;

final class DomainNameserverNormalizer
{
    /**
     * @param non-empty-string|array<int, mixed>|null $nameservers
     *
     * @return list<array{name: string, addresses?: list<string>}>
     */
    public function normalizeSimplifiedNameservers(string|array|null $nameservers): array
    {
        if (null === $nameservers) {
            throw new \InvalidArgumentException(
                'Domain register simplified API requires at least one nameserver.',
            );
        }

        if (\is_string($nameservers)) {
            return [ [ 'name' => $this->requireDomainName($nameservers) ] ];
        }

        if ([] === $nameservers) {
            throw new \InvalidArgumentException(
                'Domain register simplified API requires at least one nameserver.',
            );
        }

        return \array_values(\array_map(
            fn(mixed $nameserver, int $index): array => $this->normalizeSingleNameserver($nameserver, $index),
            $nameservers,
            \array_keys($nameservers),
        ));
    }

    /**
     * @return array{name: string, addresses?: list<string>}
     */
    private function normalizeSingleNameserver(mixed $nameserver, int $index): array
    {
        if (\is_string($nameserver)) {
            return [ 'name' => $this->requireDomainName($nameserver) ];
        }

        if (!\is_array($nameserver)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d is invalid.', $index),
            );
        }

        $name = $this->extractNameserverName($nameserver, $index);
        $addresses = $this->extractNameserverAddresses($nameserver, $index);

        if ([] === $addresses) {
            return [ 'name' => $name ];
        }

        return [
            'addresses' => $addresses,
            'name' => $name,
        ];
    }

    /**
     * @param array<string, mixed> $nameserver
     */
    private function extractNameserverName(array $nameserver, int $index): string
    {
        $name = $nameserver['name'] ?? null;

        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d requires non-empty "name".', $index),
            );
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $nameserver
     *
     * @return list<string>
     */
    private function extractNameserverAddresses(array $nameserver, int $index): array
    {
        $addresses = $nameserver['addresses'] ?? null;

        if (null === $addresses) {
            return [];
        }

        if (!\is_array($addresses)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d field "addresses" must be a list.', $index),
            );
        }

        return \array_values(\array_map(
            fn(mixed $address, int $addressIndex): string => $this->normalizeNameserverAddress(
                $address,
                $addressIndex,
            ),
            $addresses,
            \array_keys($addresses),
        ));
    }

    private function normalizeNameserverAddress(mixed $address, int $addressIndex): string
    {
        if (!\is_string($address) || '' === \trim($address)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver address at index %d is invalid.', $addressIndex),
            );
        }

        return $address;
    }

    private function requireDomainName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Domain name must be a non-empty string.');
        }

        return $name;
    }
}
