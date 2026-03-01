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
     * @var list<string>
     */
    private array $registerEnvKeys = [
        'RNIDS_EPP_REGISTER_REGISTRANT',
        'RNIDS_EPP_REGISTER_ADMIN_CONTACT',
        'RNIDS_EPP_REGISTER_TECH_CONTACT',
        'RNIDS_EPP_REGISTER_AUTH_INFO',
        'RNIDS_EPP_REGISTER_NAMESERVERS',
        'RNIDS_EPP_TEST_CONTACT_ID',
    ];

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

    public function testTestContactHandleUsesEnvironmentValueWhenPresent(): void
    {
        $this->setEnv('RNIDS_EPP_TEST_CONTACT_ID', 'OBL-ENV-001');

        self::assertSame('OBL-ENV-001', IntegrationConfig::testContactHandle());
    }

    public function testTestContactHandleFallsBackToStableDefaultHandle(): void
    {
        self::assertSame('OBL-test-kontakt', IntegrationConfig::testContactHandle());
    }

    protected function tearDown(): void
    {
        foreach ($this->registerEnvKeys as $key) {
            \putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    private function setEnv(string $name, string $value): void
    {
        \putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
