<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @return never
 */
function fail(string $message, int $exitCode = 1): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($exitCode);
}

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
 *     allowSelfSigned: bool
 *   }
 * }
 */
function clientConfig(): array
{
    $username = getenv('RNIDS_EPP_USERNAME');
    $password = getenv('RNIDS_EPP_PASSWORD');

    if (!is_string($username) || '' === trim($username)) {
        fail('Missing RNIDS_EPP_USERNAME environment variable.');
    }

    if (!is_string($password) || '' === trim($password)) {
        fail('Missing RNIDS_EPP_PASSWORD environment variable.');
    }

    return [
        'host' => 'epp-test.rnids.rs',
        'password' => $password,
        'port' => 700,
        'tls' => [
            'allowSelfSigned' => true,
            'caFilePath' => __DIR__ . '/ca-cert.pem',
            'clientCertificatePassword' => '12345',
            'clientCertificatePath' => __DIR__ . '/client-cert.pem',
        ],
        'username' => $username,
    ];
}

if ($argc < 3 || 'domain:info' !== $argv[1]) {
    fail('Usage: php test.php domain:info <domainname>', 2);
}

$domainName = trim((string) $argv[2]);

if ('' === $domainName) {
    fail('Domain name must be a non-empty string.', 2);
}

try {
    $client = new RNIDS\Client(clientConfig());
    $response = $client->domain()->info([
        'name' => $domainName,
    ]);

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (\Throwable $exception) {
    fail(
        sprintf('domain:info failed: %s', $exception->getMessage()),
    );
}
