<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

use Tests\Support\ContactFixtureFactory;

final class IntegrationConfig
{
    private const DEFAULT_HOST = 'epp-test.rnids.rs';

    private const DEFAULT_PORT = 700;

    private const DEFAULT_CERT_PASSWORD = '12345';

    private const DEFAULT_TEST_DOMAIN = 'komodarstvo.rs';

    private const DEFAULT_REGISTER_AUTH_INFO = 'Rnids-Integration-Auth-2026';

    private const DEFAULT_CONTACT_FIXTURE_SEED = 'rnids-contact-fixture';
    private const DEFAULT_TEST_CONTACT_HANDLE = 'OBL-test-kontakt';

    private const DEFAULT_REGISTER_NAMESERVERS = [
        'ns1.komodarstvo.rs',
        'ns2.komodarstvo.rs',
    ];

    private const DEFAULT_CLIENT_CERT_CANDIDATES = [
        'tests/fixtures/oblak.pem',
        'tests/fixtures/client.pem',
        'tests/Fixtures/client.pem',
        'tests/Fixtures/oblak.pem',
        'oblak.pem',
    ];

    private const DEFAULT_CA_CERT_CANDIDATES = [
        'tests/fixtures/root.pem',
        'tests/fixtures/ca-cert.pem',
        'tests/Fixtures/ca-cert.pem',
        'tests/Fixtures/root.pem',
        'root.pem',
    ];

    /**
     * @return array{
     *   host: string,
     *   port: int,
     *   username: string,
     *   password: string,
     *   tls: array{
     *     clientCertificatePath: string,
     *     clientCertificatePassword: string,
     *     caFilePath: string,
     *     allowSelfSigned: bool,
     *     verifyPeer: bool,
     *     verifyPeerName: bool
     *   }
     * }
     */
    public static function clientConfig(): array
    {
        return [
            'host' => self::DEFAULT_HOST,
            'password' => self::requiredEnv('RNIDS_EPP_PASSWORD'),
            'port' => self::DEFAULT_PORT,
            'tls' => [
                'allowSelfSigned' => true,
                'caFilePath' => self::caCertificatePath(),
                'clientCertificatePassword' => self::clientCertificatePassword(),
                'clientCertificatePath' => self::clientCertificatePath(),
                'verifyPeer' => false,
                'verifyPeerName' => false,
            ],
            'username' => self::requiredEnv('RNIDS_EPP_USERNAME'),
        ];
    }

    public static function testDomainName(): string
    {
        $domain = \getenv('RNIDS_EPP_TEST_DOMAIN');

        if (!\is_string($domain) || '' === \trim($domain)) {
            return self::DEFAULT_TEST_DOMAIN;
        }

        return $domain;
    }

    public static function ensureReadyOrSkip(): void
    {
        self::ensureEnvOrSkip('RNIDS_EPP_USERNAME');
        self::ensureEnvOrSkip('RNIDS_EPP_PASSWORD');
        self::ensureFileOrSkip(self::clientCertificatePath(), 'RNIDS client certificate');
        self::ensureFileOrSkip(self::caCertificatePath(), 'RNIDS CA certificate');
    }

    public static function ensureRegisterReadyOrSkip(): void
    {
        // Register contact handles now fall back to test contact defaults.
    }

    /**
     * @return non-empty-string
     */
    public static function uniqueRegisterDomainName(): string
    {
        $prefix = \strtolower(\date('ymdHis'));
        $randomSuffix = \strtolower(\bin2hex(\random_bytes(2)));

        return \sprintf('it%s%s.rs', $prefix, $randomSuffix);
    }

    /**
     * @return array{
     *   name: non-empty-string,
     *   period: int,
     *   periodUnit: 'y',
     *   nameservers: list<array{name: non-empty-string}>,
     *   registrant: non-empty-string,
     *   contacts: list<array{type: 'admin'|'tech', handle: non-empty-string}>,
     *   authInfo: non-empty-string
     * }
     */
    public static function domainRegisterRequest(string $domainName): array
    {
        if ('' === \trim($domainName)) {
            throw new \InvalidArgumentException('Domain name must be a non-empty string.');
        }

        return [
            'authInfo' => self::registerAuthInfo(),
            'contacts' => [
                [
                    'handle' => self::registerAdminContactHandle(),
                    'type' => 'admin',
                ],
                [
                    'handle' => self::registerTechContactHandle(),
                    'type' => 'tech',
                ],
            ],
            'name' => $domainName,
            'nameservers' => self::registerNameservers(),
            'period' => 1,
            'periodUnit' => 'y',
            'registrant' => self::registerRegistrantHandle(),
        ];
    }

    public static function registerRegistrantHandle(): string
    {
        $registrant = self::nonEmptyEnvOrNull('RNIDS_EPP_REGISTER_REGISTRANT');

        if (null !== $registrant) {
            return $registrant;
        }

        return self::testContactHandle();
    }

    public static function registerAdminContactHandle(): string
    {
        $admin = self::nonEmptyEnvOrNull('RNIDS_EPP_REGISTER_ADMIN_CONTACT');

        if (null !== $admin) {
            return $admin;
        }

        return self::testContactHandle();
    }

    public static function registerTechContactHandle(): string
    {
        $tech = self::nonEmptyEnvOrNull('RNIDS_EPP_REGISTER_TECH_CONTACT');

        if (null !== $tech) {
            return $tech;
        }

        return self::testContactHandle();
    }

    public static function contactFixtures(): ContactFixtureFactory
    {
        $seed = \getenv('RNIDS_EPP_CONTACT_FIXTURE_SEED');

        if (!\is_string($seed) || '' === \trim($seed)) {
            $seed = self::DEFAULT_CONTACT_FIXTURE_SEED;
        }

        $factory = ContactFixtureFactory::forSeed($seed);
        $runToken = \getenv('RNIDS_EPP_CONTACT_RUN_TOKEN');

        if (!\is_string($runToken) || '' === \trim($runToken)) {
            return $factory;
        }

        return $factory->withRunToken($runToken);
    }

    public static function testContactHandle(): string
    {
        $handle = \getenv('RNIDS_EPP_TEST_CONTACT_ID');

        if (!\is_string($handle) || '' === \trim($handle)) {
            return self::DEFAULT_TEST_CONTACT_HANDLE;
        }

        return $handle;
    }

    private static function clientCertificatePath(): string
    {
        return self::certificatePathFromEnvOrCandidates(
            'RNIDS_EPP_CLIENT_CERT_PATH',
            self::DEFAULT_CLIENT_CERT_CANDIDATES,
        );
    }

    private static function caCertificatePath(): string
    {
        return self::certificatePathFromEnvOrCandidates(
            'RNIDS_EPP_CA_CERT_PATH',
            self::DEFAULT_CA_CERT_CANDIDATES,
        );
    }

    private static function clientCertificatePassword(): string
    {
        $password = \getenv('RNIDS_EPP_CLIENT_CERT_PASSWORD');

        if (!\is_string($password) || '' === \trim($password)) {
            return self::DEFAULT_CERT_PASSWORD;
        }

        return $password;
    }

    private static function registerAuthInfo(): string
    {
        $authInfo = \getenv('RNIDS_EPP_REGISTER_AUTH_INFO');

        if (!\is_string($authInfo) || '' === \trim($authInfo)) {
            return self::DEFAULT_REGISTER_AUTH_INFO;
        }

        return $authInfo;
    }

    /**
     * @return list<array{name: non-empty-string}>
     */
    private static function registerNameservers(): array
    {
        $rawNameservers = \getenv('RNIDS_EPP_REGISTER_NAMESERVERS');

        if (!\is_string($rawNameservers) || '' === \trim($rawNameservers)) {
            return \array_map(
                static fn(string $host): array => [ 'name' => $host ],
                self::DEFAULT_REGISTER_NAMESERVERS,
            );
        }

        $nameservers = \array_values(\array_filter(
            \array_map(static fn(string $value): string => \trim($value), \explode(',', $rawNameservers)),
            static fn(string $value): bool => '' !== $value,
        ));

        if ([] === $nameservers) {
            throw new \RuntimeException(
                'Environment variable "RNIDS_EPP_REGISTER_NAMESERVERS" must contain at least one host.',
            );
        }

        return \array_map(
            static fn(string $host): array => [ 'name' => $host ],
            $nameservers,
        );
    }

    /**
     * @param list<string> $candidates
     */
    private static function certificatePathFromEnvOrCandidates(string $envName, array $candidates): string
    {
        $envPath = self::nonEmptyEnvOrNull($envName);

        if (null !== $envPath) {
            return $envPath;
        }

        $projectRoot = \dirname(__DIR__, 3);

        return self::firstReadableCandidatePath($projectRoot, $candidates)
            ?? $projectRoot . '/' . $candidates[0];
    }

    private static function nonEmptyEnvOrNull(string $envName): ?string
    {
        $value = \getenv($envName);

        if (!\is_string($value) || '' === \trim($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param list<string> $candidates
     */
    private static function firstReadableCandidatePath(string $projectRoot, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $candidatePath = $projectRoot . '/' . $candidate;

            if (\is_file($candidatePath) && \is_readable($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    private static function ensureEnvOrSkip(string $name): void
    {
        $value = \getenv($name);

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \PHPUnit\Framework\SkippedTestSuiteError(
                \sprintf('Missing required environment variable "%s" for RNIDS integration tests.', $name),
            );
        }
    }

    private static function ensureFileOrSkip(string $path, string $label): void
    {
        if (\is_file($path) && \is_readable($path)) {
            return;
        }

        throw new \PHPUnit\Framework\SkippedTestSuiteError(
            \sprintf('Missing readable %s file at "%s".', $label, $path),
        );
    }

    private static function requiredEnv(string $name): string
    {
        $value = \getenv($name);

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \RuntimeException(
                \sprintf('Missing required environment variable "%s".', $name),
            );
        }

        return $value;
    }
}
