<?php

declare(strict_types=1);

namespace RNIDS\Session;

use RNIDS\Session\Dto\LoginRequest;
use RNIDS\Session\Dto\PollRequest;

final class SessionInputNormalizer
{
    /**
     * @param array{
     *   clientId: non-empty-string,
     *   password: non-empty-string,
     *   version?: non-empty-string,
     *   language?: non-empty-string,
     *   objectUris?: list<non-empty-string>,
     *   extensionUris?: list<non-empty-string>
     * } $request
     */
    public function buildLoginRequest(array $request): LoginRequest
    {
        return new LoginRequest(
            clientId: $this->requireString($request, 'clientId'),
            password: $this->requireString($request, 'password'),
            version: $this->optionalString($request, 'version', '1.0'),
            language: $this->optionalString($request, 'language', 'en'),
            objectUris: $this->optionalStringList($request, 'objectUris'),
            extensionUris: $this->optionalStringList($request, 'extensionUris'),
        );
    }

    /**
     * @param array{messageId?: mixed, operation?: mixed} $request
     */
    public function buildPollRequest(array $request): PollRequest
    {
        $operation = $this->optionalPollOperation($request);

        if ('ack' === $operation) {
            return new PollRequest($operation, $this->requireString($request, 'messageId'));
        }

        return new PollRequest($operation, $this->optionalNullableString($request, 'messageId'));
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requireString(array $request, string $key): string
    {
        $value = $request[$key] ?? null;

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalString(array $request, string $key, string $default): string
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return $default;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return list<string>
     */
    private function optionalStringList(array $request, string $key): array
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return [];
        }

        if (!\is_array($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a list of strings.', $key),
            );
        }

        $result = [];

        foreach ($value as $item) {
            $result[] = $this->requireStringListItem($item, $key);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalNullableString(array $request, string $key): ?string
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array{operation?: mixed} $request
     */
    private function optionalPollOperation(array $request): string
    {
        $operation = $request['operation'] ?? 'req';

        if (!\is_string($operation) || !\in_array($operation, [ 'req', 'ack' ], true)) {
            throw new \InvalidArgumentException(
                'Session poll request key "operation" must be either "req" or "ack".',
            );
        }

        return $operation;
    }

    private function requireStringListItem(mixed $item, string $key): string
    {
        if (!\is_string($item) || '' === \trim($item)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must contain only non-empty strings.', $key),
            );
        }

        return $item;
    }
}
