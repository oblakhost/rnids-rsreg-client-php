<?php

declare(strict_types=1);

namespace RNIDS\Host;

final class HostInputNormalizer
{
    public function requireHostName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Host name must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return array{names: list<string>|mixed}
     */
    public function normalizeCheckRequest(string|array $request): array
    {
        if (\is_string($request)) {
            return [ 'names' => [ $this->requireHostName($request) ] ];
        }

        if (isset($request['names'])) {
            return [ 'names' => $request['names'] ];
        }

        if ([] === $request) {
            return [ 'names' => [] ];
        }

        return [
            'names' => \array_values(\array_map(
                fn(mixed $name): string => $this->requireHostNameFromMixed($name),
                $request,
            )),
        ];
    }

    /**
     * @param array{name?: mixed, addresses?: mixed}|non-empty-string $request
     *
     * @return array{name: string, addresses: list<array{address: string, ipVersion: string}>}|array<string, mixed>
     */
    public function normalizeCreateRequest(string|array $request, ?string $ipv4, ?string $ipv6): array
    {
        if (\is_array($request)) {
            return $request;
        }

        $addresses = $this->buildCreateAddresses($ipv4, $ipv6);

        return [
            'addresses' => $addresses,
            'name' => $this->requireHostName($request),
        ];
    }

    private function requireHostNameFromMixed(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException(
                'Host check request key "names" must contain only non-empty strings.',
            );
        }

        return $this->requireHostName($name);
    }

    /**
     * @return list<array{address: string, ipVersion: string}>
     */
    private function buildCreateAddresses(?string $ipv4, ?string $ipv6): array
    {
        $addresses = [];

        $this->appendAddress(
            $addresses,
            $ipv4,
            'v4',
            'Host create ipv4 must be a non-empty string when provided.',
        );
        $this->appendAddress(
            $addresses,
            $ipv6,
            'v6',
            'Host create ipv6 must be a non-empty string when provided.',
        );

        return $addresses;
    }

    /**
     * @param list<array{address: string, ipVersion: string}> $addresses
     */
    private function appendAddress(array &$addresses, ?string $address, string $ipVersion, string $error): void
    {
        if (null === $address) {
            return;
        }

        if ('' === \trim($address)) {
            throw new \InvalidArgumentException($error);
        }

        $addresses[] = [
            'address' => $address,
            'ipVersion' => $ipVersion,
        ];
    }
}
