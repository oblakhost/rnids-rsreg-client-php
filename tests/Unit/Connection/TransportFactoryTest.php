<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\NativeStreamTransport;
use RNIDS\Connection\TlsConfig;
use RNIDS\Connection\Transport;
use RNIDS\Connection\TransportFactory;

#[Group('unit')]
final class TransportFactoryTest extends TestCase
{
    public function testCreateReturnsNativeStreamTransport(): void
    {
        $transport = (new TransportFactory())->create(
            new ConnectionConfig('epp.example.rs', 700),
            new TlsConfig('/tmp/client.pem'),
        );

        self::assertInstanceOf(Transport::class, $transport);
        self::assertInstanceOf(NativeStreamTransport::class, $transport);
    }
}
