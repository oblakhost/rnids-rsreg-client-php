<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use RNIDS\Connection\EppFrameCodec;

final class EppFrameCodecTest extends TestCase
{
    public function testEncodeAddsLengthPrefix(): void
    {
        $codec = new EppFrameCodec();

        $frame = $codec->encode('<epp/>');

        self::assertSame(10, \strlen($frame));
        self::assertSame(6, \unpack('Nlength', \substr($frame, 0, 4))['length'] - 4);
        self::assertSame('<epp/>', \substr($frame, 4));
    }

    public function testDecodeLengthPrefixReturnsPayloadLength(): void
    {
        $codec = new EppFrameCodec();

        self::assertSame(42, $codec->decodeLengthPrefix(\pack('N', 46)));
    }
}
