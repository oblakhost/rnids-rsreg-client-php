<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Config\ClientConfigFactory;
use RNIDS\Xml\NamespaceRegistry;

#[Group('unit')]
final class ClientConfigFactoryTest extends TestCase
{
    public function testFromArrayBuildsTypedConfigWithDefaults(): void
    {
        $config = ClientConfigFactory::fromArray([
            'host' => 'epp.example.rs',
            'password' => 'secret',
            'username' => 'client-id',
        ]);

        self::assertSame('epp.example.rs', $config->connectionConfig->hostname);
        self::assertSame(700, $config->connectionConfig->port);
        self::assertSame(10, $config->connectionConfig->connectTimeoutSeconds);
        self::assertSame(20, $config->connectionConfig->readTimeoutSeconds);
        self::assertSame('client-id', $config->username);
        self::assertSame('secret', $config->password);
        self::assertSame('en', $config->language);
        self::assertSame('1.0', $config->version);
        self::assertSame([
            NamespaceRegistry::DOMAIN,
            NamespaceRegistry::CONTACT,
            NamespaceRegistry::HOST,
        ], $config->objectUris);
        self::assertSame([ NamespaceRegistry::RNIDS ], $config->extensionUris);
        self::assertNull($config->tlsConfig);
    }

    public function testFromArrayThrowsForInvalidRequiredString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Client config key "host" must be a non-empty string.');

        ClientConfigFactory::fromArray([
            'host' => '',
            'password' => 'secret',
            'username' => 'client-id',
        ]);
    }

    public function testFromArrayReturnsTlsConfigWhenCertificatePathProvided(): void
    {
        $config = ClientConfigFactory::fromArray([
            'host' => 'epp.example.rs',
            'password' => 'secret',
            'tls' => [
                'allowSelfSigned' => true,
                'caFilePath' => '/tmp/ca.pem',
                'clientCertificatePath' => '/tmp/client.pem',
                'verifyPeer' => false,
                'verifyPeerName' => true,
            ],
            'username' => 'client-id',
        ]);

        self::assertNotNull($config->tlsConfig);
        self::assertSame('/tmp/client.pem', $config->tlsConfig->clientCertificatePath);
        self::assertSame('/tmp/ca.pem', $config->tlsConfig->caFilePath);
        self::assertTrue($config->tlsConfig->allowSelfSigned);
        self::assertFalse($config->tlsConfig->verifyPeer);
        self::assertTrue($config->tlsConfig->verifyPeerName);
    }
}
