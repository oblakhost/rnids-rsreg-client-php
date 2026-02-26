<?php

declare(strict_types=1);

namespace RNIDS\Connection;

final class EppFrameCodec
{
    public function encode(string $payload): string
    {
        return \pack('N', \strlen($payload) + 4) . $payload;
    }

    public function decodeLengthPrefix(string $prefix): int
    {
        if (4 !== \strlen($prefix)) {
            throw new \InvalidArgumentException('EPP frame prefix must be exactly 4 bytes long.');
        }

        $result = \unpack('Nlength', $prefix);

        if (false === $result || !isset($result['length'])) {
            throw new \InvalidArgumentException('Unable to decode EPP frame prefix.');
        }

        return $result['length'] - 4;
    }
}
