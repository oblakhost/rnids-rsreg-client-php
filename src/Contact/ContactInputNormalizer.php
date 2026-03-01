<?php

declare(strict_types=1);

namespace RNIDS\Contact;

final class ContactInputNormalizer
{
    /**
     * @param array{ids?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return array{ids: list<string>|mixed}
     */
    public function normalizeCheckRequest(string|array $request): array
    {
        if (\is_string($request)) {
            return [ 'ids' => [ $this->requireCheckId($request) ] ];
        }

        if (isset($request['ids'])) {
            return [ 'ids' => $request['ids'] ];
        }

        return [ 'ids' => $this->normalizeCheckIdsList($request) ];
    }

    public function requireContactId(string $id): string
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact id must be a non-empty string.');
        }

        return $id;
    }

    private function requireCheckId(string $id): string
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact check id must be a non-empty string.');
        }

        return $id;
    }

    /**
     * @param list<mixed> $request
     *
     * @return list<string>
     */
    private function normalizeCheckIdsList(array $request): array
    {
        if ([] === $request) {
            return [];
        }

        return [
            ...\array_values(\array_map(
                static function (mixed $value): string {
                    if (!\is_string($value) || '' === \trim($value)) {
                        throw new \InvalidArgumentException(
                            'Contact check request list must contain only non-empty strings.',
                        );
                    }

                    return $value;
                },
                $request,
            )),
        ];
    }
}
