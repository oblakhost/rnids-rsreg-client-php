<?php

declare(strict_types=1);

namespace RNIDS\Connection;

/**
 * Handles EPP frame length-prefix encoding and decoding.
 */
final class EppFrameCodec
{
    /**
     * Encodes an XML payload as an EPP frame.
     *
     * @param string $payload XML payload without the frame prefix.
     */
    public function encode(string $payload): string
    {
        return \pack('N', \strlen($payload) + 4) . $payload;
    }

    /**
     * Decodes a 4-byte EPP frame prefix into payload length.
     *
     * @param string $prefix 4-byte network-order frame prefix.
     */
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
