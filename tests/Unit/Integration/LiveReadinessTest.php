<?php

declare(strict_types=1);

namespace Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\CertificateReadiness;
use Tests\Integration\Support\IntegrationConfig;

final class LiveReadinessTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $environment = [];
    /** @var list<string> */
    private array $files = [];

    public function testAutomaticDiscoveryNeverSelectsDummyCertificate(): void
    {
        $this->env('RNIDS_EPP_USERNAME', 'local-readiness-test');
        $this->env('RNIDS_EPP_PASSWORD', 'local-test-password');
        $this->env('RNIDS_EPP_CLIENT_CERT_PATH', '');

        self::assertStringNotContainsString(
            'dummy-client-cert.pem',
            IntegrationConfig::clientConfig()['tls']['clientCertificatePath'],
        );
    }

    public function testMissingCredentialsStopBeforeConnectivityProbe(): void
    {
        $this->env('RNIDS_EPP_USERNAME', '');
        $this->env('RNIDS_EPP_PASSWORD', '');
        $this->env('RNIDS_EPP_HOST', '127.0.0.1');
        $this->env('RNIDS_EPP_PORT', '1');
        $probed = false;
        $reason = IntegrationConfig::liveReadinessFailureReason(
            static function () use (&$probed): bool {
                $probed = true;
                return true;
            },
        );

        self::assertStringContainsString('missing RNIDS_EPP_USERNAME', $reason);
        self::assertFalse($probed);
    }

    public function testInvalidCertificateIsReportedWithoutItsContents(): void
    {
        $path = $this->file('secret-certificate-material');
        $reason = CertificateReadiness::failureReason($path, 'secret-password', true);

        self::assertSame('certificate is not a valid PEM X.509 certificate', $reason);
    }

    public function testInvalidCertificateStopsBeforeConnectivityWithCredentialsPresent(): void
    {
        $this->env('RNIDS_EPP_USERNAME', 'local-readiness-test');
        $this->env('RNIDS_EPP_PASSWORD', 'local-test-password');
        $this->env('RNIDS_EPP_CLIENT_CERT_PATH', $this->file('invalid-pem'));
        $probed = false;
        $reason = IntegrationConfig::liveReadinessFailureReason(
            static function () use (&$probed): bool {
                $probed = true;
                return true;
            },
        );
        self::assertStringContainsString('client certificate is not a valid PEM', $reason);
        self::assertFalse($probed);
    }

    public function testEncryptedPrivateKeyUsesProvidedPassword(): void
    {
        [ $certificate, $key ] = $this->certificate();
        \openssl_pkey_export($key, $encryptedKey, 'local-secret');
        $path = $this->file($certificate . $encryptedKey);

        self::assertNull(CertificateReadiness::failureReason($path, 'local-secret', true));
        self::assertSame(
            'certificate private key is missing, unreadable, or has an incorrect password',
            CertificateReadiness::failureReason($path, 'incorrect', true),
        );
    }

    public function testCertificateRequiresMatchingPrivateKey(): void
    {
        [ $certificate, $key ] = $this->certificate();
        $otherKey = $this->certificate()[1];

        self::assertNull(CertificateReadiness::failureReason($this->file($certificate . $key), '', true));
        self::assertSame(
            'certificate private key is missing, unreadable, or has an incorrect password',
            CertificateReadiness::failureReason($this->file($certificate), '', true),
        );
        self::assertSame(
            'certificate and private key do not match',
            CertificateReadiness::failureReason($this->file($certificate . $otherKey), '', true),
        );
    }

    public function testCertificateValidityWindowIsChecked(): void
    {
        [ $certificate, $key ] = $this->certificate();
        $path = $this->file($certificate . $key);
        $parsed = \openssl_x509_parse($certificate);

        self::assertSame(
            'certificate has expired',
            CertificateReadiness::failureReason($path, '', true, $parsed['validTo_time_t'] + 1),
        );
        self::assertSame(
            'certificate is not valid yet',
            CertificateReadiness::failureReason($path, '', true, $parsed['validFrom_time_t'] - 1),
        );
        self::assertNull(CertificateReadiness::failureReason($this->file($certificate), '', false));
    }

    public function testValidLocalPrerequisitesUseInjectedProbe(): void
    {
        [ $certificate, $key ] = $this->certificate();
        $this->env('RNIDS_EPP_USERNAME', 'local-readiness-test');
        $this->env('RNIDS_EPP_PASSWORD', 'local-test-password');
        $this->env('RNIDS_EPP_CLIENT_CERT_PATH', $this->file($certificate . $key));
        $this->env('RNIDS_EPP_CLIENT_CERT_PASSWORD', '');
        $this->env('RNIDS_EPP_CA_CERT_PATH', $this->file($certificate));
        $this->env('RNIDS_EPP_HOST', 'local.invalid');
        $this->env('RNIDS_EPP_PORT', '700');
        $endpoint = null;
        $reason = IntegrationConfig::liveReadinessFailureReason(
            static function (string $host, int $port, float $timeout) use (&$endpoint): bool {
                $endpoint = [ $host, $port, $timeout ];
                return true;
            },
        );

        self::assertNull($reason);
        self::assertSame([ 'local.invalid', 700, 2.0 ], $endpoint);
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            \putenv(false === $value ? $name : $name . '=' . $value);
        }
        foreach ($this->files as $file) {
            \unlink($file);
        }
    }

    private function env(string $name, string $value): void
    {
        $this->environment[$name] = \getenv($name);
        \putenv($name . '=' . $value);
    }

    private function file(string $contents): string
    {
        $path = \tempnam(\sys_get_temp_dir(), 'rnids-readiness-');
        \file_put_contents($path, $contents);
        $this->files[] = $path;
        return $path;
    }

    /** @return array{0: string, 1: string} */
    private function certificate(): array
    {
        $key = \openssl_pkey_new([ 'private_key_bits' => 2048 ]);
        $csr = \openssl_csr_new([ 'commonName' => 'local-readiness-test' ], $key);
        $certificate = \openssl_csr_sign($csr, null, $key, 1);
        \openssl_x509_export($certificate, $pem);
        \openssl_pkey_export($key, $privateKey);
        return [ $pem, $privateKey ];
    }
}
