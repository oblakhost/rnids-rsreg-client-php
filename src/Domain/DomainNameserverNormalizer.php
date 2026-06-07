<?php

declare(strict_types=1);

namespace RNIDS\Domain;

final class DomainNameserverNormalizer
{
    /**
     * @param non-empty-string|array<int, mixed>|null $nameservers
     *
     * @return list<array{name: string, addresses?: list<array{address: string, ipVersion: string}>}>
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
     * @return array{name: string, addresses?: list<array{address: string, ipVersion: string}>}
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
     * @return list<array{address: string, ipVersion: string}>
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
            fn(mixed $address, int $addressIndex): array => $this->normalizeNameserverAddress(
                $address,
                $addressIndex,
            ),
            $addresses,
            \array_keys($addresses),
        ));
    }

    /**
     * @return array{address: string, ipVersion: string}
     */
    private function normalizeNameserverAddress(mixed $address, int $addressIndex): array
    {
        if (\is_string($address) && '' !== \trim($address)) {
            return $this->normalizeStringAddress($address);
        }

        if (\is_array($address)) {
            return $this->normalizeStructuredAddress($address, $addressIndex);
        }

        throw $this->invalidNameserverAddress($addressIndex);
    }

    /**
     * @return array{address: string, ipVersion: string}
     */
    private function normalizeStringAddress(string $address): array
    {
        return [
            'address' => $address,
            'ipVersion' => $this->inferIpVersion($address),
        ];
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array{address: string, ipVersion: string}
     */
    private function normalizeStructuredAddress(array $address, int $addressIndex): array
    {
        $addressValue = $address['address'] ?? null;
        $ipVersion = $address['ipVersion'] ?? null;

        if (!\is_string($addressValue) || '' === \trim($addressValue)) {
            throw $this->invalidNameserverAddress($addressIndex);
        }

        if (!\is_string($ipVersion) || !\in_array($ipVersion, [ 'v4', 'v6' ], true)) {
            throw $this->invalidNameserverAddress($addressIndex);
        }

        return [
            'address' => $addressValue,
            'ipVersion' => $ipVersion,
        ];
    }

    private function invalidNameserverAddress(int $addressIndex): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            \sprintf('Domain register nameserver address at index %d is invalid.', $addressIndex),
        );
    }

    private function requireDomainName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Domain name must be a non-empty string.');
        }

        return $name;
    }

    private function inferIpVersion(string $address): string
    {
        if (false !== \filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'v6';
        }

        return 'v4';
    }
}
