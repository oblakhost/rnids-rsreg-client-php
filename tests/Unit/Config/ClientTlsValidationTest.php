<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RNIDS\Client;
use RNIDS\Config\ClientConfigFactory;

final class ClientTlsValidationTest extends TestCase
{
    /** @return iterable<string, array{0: mixed}> */
    public static function invalidTlsConfigurations(): iterable
    {
        yield 'wrong type' => [ 'invalid' ];
        yield 'explicit null' => [ null ];
        yield 'no certificate' => [ [ 'verifyPeer' => true ] ];
        yield 'blank certificate' => [ [ 'clientCertificatePath' => ' ' ] ];
        yield 'string boolean' => [ [ 'clientCertificatePath' => 'client.pem', 'verifyPeer' => 'false' ] ];
        yield 'invalid CA type' => [ [ 'clientCertificatePath' => 'client.pem', 'caFilePath' => 42 ] ];
        yield 'misspelled certificate field' => [ [ 'clientCertPath' => 'client.pem' ] ];
    }

    #[DataProvider('invalidTlsConfigurations')]
    public function testMalformedTlsConfigurationFailsInsteadOfSelectingPlaintext(mixed $tls): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ClientConfigFactory::fromArray($this->baseConfig() + [ 'tls' => $tls ]);
    }

    public function testNativePlaintextRequiresExplicitOptIn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client($this->baseConfig());
    }

    public function testExplicitPlaintextAllowsConstructingLocalTestClient(): void
    {
        $client = new Client($this->baseConfig() + [ 'allowPlaintext' => true ]);
        self::assertInstanceOf(\RNIDS\Connection\NativeStreamTransport::class, $client->transport());
    }

    /** @return array{host: string, username: string, password: string} */
    private function baseConfig(): array
    {
        return [ 'host' => 'localhost', 'username' => 'test', 'password' => 'secret' ];
    }
}
