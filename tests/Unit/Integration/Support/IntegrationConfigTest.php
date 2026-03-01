<?php

declare(strict_types=1);

namespace Tests\Unit\Integration\Support;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\IntegrationConfig;

#[Group('unit')]
final class IntegrationConfigTest extends TestCase
{
    /**
     * @var array<string, string|null>
     */
    private array $originalEnvValues = [];

    public function testUniqueRegisterDomainNameReturnsExpectedFormat(): void
    {
        $domain = IntegrationConfig::uniqueRegisterDomainName();

        self::assertStringStartsWith('it', $domain);
        self::assertStringEndsWith('.rs', $domain);
        self::assertMatchesRegularExpression('/^it[a-z0-9]+\.rs$/', $domain);
    }

    public function testDomainRegisterRequestUsesConfiguredEnvironmentValues(): void
    {
        $this->setEnv('RNIDS_EPP_REGISTER_REGISTRANT', 'REG-555');
        $this->setEnv('RNIDS_EPP_REGISTER_ADMIN_CONTACT', 'ADM-555');
        $this->setEnv('RNIDS_EPP_REGISTER_TECH_CONTACT', 'TEC-555');
        $this->setEnv('RNIDS_EPP_REGISTER_AUTH_INFO', 'auth-555');
        $this->setEnv('RNIDS_EPP_REGISTER_NAMESERVERS', 'ns1.example.rs, ns2.example.rs');

        $request = IntegrationConfig::domainRegisterRequest('example.rs');

        self::assertSame('example.rs', $request['name']);
        self::assertSame('REG-555', $request['registrant']);
        self::assertSame('ADM-555', $request['contacts'][0]['handle']);
        self::assertSame('admin', $request['contacts'][0]['type']);
        self::assertSame('TEC-555', $request['contacts'][1]['handle']);
        self::assertSame('tech', $request['contacts'][1]['type']);
        self::assertSame('auth-555', $request['authInfo']);
        self::assertSame(
            [
                [ 'name' => 'ns1.example.rs' ],
                [ 'name' => 'ns2.example.rs' ],
            ],
            $request['nameservers'],
        );
    }

    public function testDomainRegisterRequestThrowsForEmptyDomain(): void
    {
        $this->setEnv('RNIDS_EPP_REGISTER_REGISTRANT', 'REG-555');
        $this->setEnv('RNIDS_EPP_REGISTER_ADMIN_CONTACT', 'ADM-555');
        $this->setEnv('RNIDS_EPP_REGISTER_TECH_CONTACT', 'TEC-555');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain name must be a non-empty string.');

        IntegrationConfig::domainRegisterRequest('');
    }

    public function testDomainRegisterRequestFallsBackToTestContactEnvWhenRegistrantIsMissing(): void
    {
        $this->setEnv('RNIDS_EPP_REGISTER_ADMIN_CONTACT', 'ADM-555');
        $this->setEnv('RNIDS_EPP_REGISTER_TECH_CONTACT', 'TEC-555');
        $this->setEnv('RNIDS_EPP_TEST_CONTACT_ID', 'OBL-ENV-001');

        $request = IntegrationConfig::domainRegisterRequest('example.rs');

        self::assertSame('OBL-ENV-001', $request['registrant']);
    }

    public function testDomainRegisterRequestFallsBackToDefaultContactWhenRegistrantAndTestContactEnvMissing(): void
    {
        $this->setEnv('RNIDS_EPP_REGISTER_ADMIN_CONTACT', 'ADM-555');
        $this->setEnv('RNIDS_EPP_REGISTER_TECH_CONTACT', 'TEC-555');

        $request = IntegrationConfig::domainRegisterRequest('example.rs');

        self::assertSame('OBL-test-kontakt', $request['registrant']);
    }

    public function testDomainRegisterRequestFallsBackToTestContactForAdminAndTechWhenMissing(): void
    {
        $this->setEnv('RNIDS_EPP_TEST_CONTACT_ID', 'OBL-ENV-001');

        $request = IntegrationConfig::domainRegisterRequest('example.rs');

        self::assertSame('OBL-ENV-001', $request['contacts'][0]['handle']);
        self::assertSame('admin', $request['contacts'][0]['type']);
        self::assertSame('OBL-ENV-001', $request['contacts'][1]['handle']);
        self::assertSame('tech', $request['contacts'][1]['type']);
    }

    public function testDomainRegisterRequestFallsBackToDefaultContactForAdminAndTechWhenAllEnvMissing(): void
    {
        $request = IntegrationConfig::domainRegisterRequest('example.rs');

        self::assertSame('OBL-test-kontakt', $request['contacts'][0]['handle']);
        self::assertSame('admin', $request['contacts'][0]['type']);
        self::assertSame('OBL-test-kontakt', $request['contacts'][1]['handle']);
        self::assertSame('tech', $request['contacts'][1]['type']);
    }

    public function testTestContactHandleUsesEnvironmentValueWhenPresent(): void
    {
        $this->setEnv('RNIDS_EPP_TEST_CONTACT_ID', 'OBL-ENV-001');

        self::assertSame('OBL-ENV-001', IntegrationConfig::testContactHandle());
    }

    public function testTestContactHandleFallsBackToStableDefaultHandle(): void
    {
        self::assertSame('OBL-test-kontakt', IntegrationConfig::testContactHandle());
    }

    public function testClientConfigUsesRelaxedTlsVerificationForRnidsTestEndpoint(): void
    {
        $this->setEnv('RNIDS_EPP_USERNAME', 'user-1');
        $this->setEnv('RNIDS_EPP_PASSWORD', 'pass-1');

        $config = IntegrationConfig::clientConfig();

        self::assertArrayHasKey('tls', $config);
        self::assertFalse($config['tls']['verifyPeer']);
        self::assertFalse($config['tls']['verifyPeerName']);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvValues as $key => $value) {
            if (null === $value) {
                \putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);

                continue;
            }

            \putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->originalEnvValues = [];

        parent::tearDown();
    }

    private function setEnv(string $name, string $value): void
    {
        if (!\array_key_exists($name, $this->originalEnvValues)) {
            $current = \getenv($name);
            $this->originalEnvValues[$name] = \is_string($current) ? $current : null;
        }

        \putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
