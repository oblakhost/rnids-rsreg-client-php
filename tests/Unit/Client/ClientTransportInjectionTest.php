<?php

declare(strict_types=1);

namespace Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Connection\Transport;

final class ClientTransportInjectionTest extends TestCase
{
    public function testClientUsesProvidedTransportWithoutConnectingDuringConstruction(): void
    {
        $transport = $this->createMock(Transport::class);
        $transport->expects(self::never())->method('connect');
        $client = new Client([
            'host' => 'unused.invalid',
            'password' => 'secret',
            'username' => 'audit',
        ], $transport);

        self::assertSame($transport, $client->transport());
    }
}
