<?php

declare(strict_types=1);

namespace RNIDS\Contact;

final class ContactIdPolicy
{
    public const PREFIX = 'OBL-';

    public function normalizeForCreate(mixed $id): string
    {
        if (null === $id) {
            return $this->generateId();
        }

        if (!\is_string($id)) {
            throw new \InvalidArgumentException(
                'Contact create request key "id" must be a string when provided.',
            );
        }

        $trimmed = \trim($id);

        if ('' === $trimmed) {
            return $this->generateId();
        }

        return $this->ensurePrefix($trimmed);
    }

    public function normalizeForUpdate(mixed $id): string
    {
        if (!\is_string($id) || '' === \trim($id)) {
            throw new \InvalidArgumentException(
                'Contact update request key "id" must be a non-empty string.',
            );
        }

        return $this->ensurePrefix(\trim($id));
    }

    private function generateId(): string
    {
        return self::PREFIX . \uniqid();
    }

    private function ensurePrefix(string $id): string
    {
        if (\str_starts_with($id, self::PREFIX)) {
            return $id;
        }

        return self::PREFIX . $id;
    }
}
