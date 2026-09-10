<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

/** Validates local live-test credentials without exposing PEM data or OpenSSL diagnostics. */
final class CertificateReadiness
{
    public static function failureReason(
        string $path,
        string $password,
        bool $requirePrivateKey,
        ?int $now = null,
    ): ?string {
        if (!\is_file($path) || !\is_readable($path)) {
            return 'certificate file is not readable';
        }

        $pem = @\file_get_contents($path);
        $certificate = @\openssl_x509_read((string) $pem);
        if (false === $certificate) {
            return 'certificate is not a valid PEM X.509 certificate';
        }

        $validityIssue = self::validityFailureReason($certificate, $now ?? \time());
        if (null !== $validityIssue) {
            return $validityIssue;
        }

        return $requirePrivateKey ? self::keyFailureReason($certificate, $pem, $password) : null;
    }

    private static function validityFailureReason(\OpenSSLCertificate $certificate, int $now): ?string
    {
        $details = \openssl_x509_parse($certificate);
        if (false === $details || !isset($details['validFrom_time_t'], $details['validTo_time_t'])) {
            return 'certificate validity dates cannot be read';
        }
        if ($now < $details['validFrom_time_t']) {
            return 'certificate is not valid yet';
        }
        if ($now >= $details['validTo_time_t']) {
            return 'certificate has expired';
        }

        return null;
    }

    private static function keyFailureReason(
        \OpenSSLCertificate $certificate,
        string $pem,
        string $password,
    ): ?string {
        $key = @\openssl_pkey_get_private($pem, $password);
        if (false === $key) {
            return 'certificate private key is missing, unreadable, or has an incorrect password';
        }
        if (!\openssl_x509_check_private_key($certificate, $key)) {
            return 'certificate and private key do not match';
        }

        return null;
    }
}
