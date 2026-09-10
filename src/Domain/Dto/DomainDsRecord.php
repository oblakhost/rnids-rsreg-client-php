<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/** A DS record supported by the RNIDS secDNS data interface. */
final class DomainDsRecord
{
    public function __construct(
        public readonly int $keyTag,
        public readonly int $alg,
        public readonly int $digestType,
        public readonly string $digest,
    ) {
        if ($keyTag < 0 || $keyTag > 65535 || !\in_array($alg, [ 3, 5, 6, 7, 8, 10, 13, 14 ], true)) {
            throw new \InvalidArgumentException('Invalid RNIDS DS key tag or algorithm.');
        }

        $lengths = [ 1 => 40, 2 => 64, 3 => 64, 4 => 96 ];
        $isHex = 1 === \preg_match('/\A[0-9a-fA-F]+\z/', $digest);
        if (!isset($lengths[$digestType]) || \strlen($digest) !== $lengths[$digestType] || !$isHex) {
            throw new \InvalidArgumentException(
                'Invalid RNIDS DS digest type, length, or hexadecimal encoding.',
            );
        }
    }
}
