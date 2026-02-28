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
 * @param list<string> $candidates
 */
function resolvePathFromEnvOrCandidates(string $envName, array $candidates): string
{
    $envPath = getenv($envName);

    if (is_string($envPath) && '' !== trim($envPath)) {
        return $envPath;
    }

    foreach ($candidates as $candidate) {
        $candidatePath = __DIR__ . '/' . $candidate;

        if (is_file($candidatePath) && is_readable($candidatePath)) {
            return $candidatePath;
        }
    }

    return __DIR__ . '/' . $candidates[0];
}

function envStringOrDefault(string $envName, string $default): string
{
    $value = getenv($envName);

    if (!is_string($value) || '' === trim($value)) {
        return $default;
    }

    return $value;
}

function envBoolOrDefault(string $envName, bool $default): bool
{
    $value = getenv($envName);

    if (!is_string($value) || '' === trim($value)) {
        return $default;
    }

    $normalized = strtolower(trim($value));

    if (in_array($normalized, [ '1', 'true', 'yes', 'on' ], true)) {
        return true;
    }

    if (in_array($normalized, [ '0', 'false', 'no', 'off' ], true)) {
        return false;
    }

    fail(
        sprintf(
            '%s must be one of: 1,true,yes,on,0,false,no,off. Got: %s',
            $envName,
            $value,
        ),
        2,
    );
}

function assertReadableFile(string $path, string $label): void
{
    if ('' !== trim($path) && is_file($path) && is_readable($path)) {
        return;
    }

    fail(sprintf('%s file is not readable: %s', $label, $path));
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
 *     allowSelfSigned: bool,
 *     verifyPeer: bool,
 *     verifyPeerName: bool,
 *     peerName?: string
 *   }
 * }
 */
function clientConfig(): array
{
    $username = getenv('RNIDS_EPP_USERNAME');
    $password = getenv('RNIDS_EPP_PASSWORD');
    $clientCertificatePath = resolvePathFromEnvOrCandidates(
        'RNIDS_EPP_CLIENT_CERT_PATH',
        [
            'tests/fixtures/oblak.pem',
            'tests/fixtures/client.pem',
            'tests/Fixtures/client.pem',
            'tests/Fixtures/oblak.pem',
            'oblak.pem',
        ],
    );
    $caCertificatePath = resolvePathFromEnvOrCandidates(
        'RNIDS_EPP_CA_CERT_PATH',
        [
            'tests/fixtures/root.pem',
            'tests/fixtures/ca-cert.pem',
            'tests/Fixtures/ca-cert.pem',
            'tests/Fixtures/root.pem',
            'root.pem',
        ],
    );
    $clientCertificatePassword = envStringOrDefault('RNIDS_EPP_CLIENT_CERT_PASSWORD', '12345');
    $allowSelfSigned = envBoolOrDefault('RNIDS_EPP_TLS_ALLOW_SELF_SIGNED', true);
    $verifyPeer = envBoolOrDefault('RNIDS_EPP_TLS_VERIFY_PEER', false);
    $verifyPeerName = envBoolOrDefault('RNIDS_EPP_TLS_VERIFY_PEER_NAME', false);
    $peerName = getenv('RNIDS_EPP_TLS_PEER_NAME');

    if (!is_string($username) || '' === trim($username)) {
        fail('Missing RNIDS_EPP_USERNAME environment variable.');
    }

    if (!is_string($password) || '' === trim($password)) {
        fail('Missing RNIDS_EPP_PASSWORD environment variable.');
    }

    assertReadableFile($clientCertificatePath, 'RNIDS client certificate');
    assertReadableFile($caCertificatePath, 'RNIDS CA certificate');

    $tls = [
        'allowSelfSigned' => $allowSelfSigned,
        'caFilePath' => $caCertificatePath,
        'clientCertificatePassword' => $clientCertificatePassword,
        'clientCertificatePath' => $clientCertificatePath,
        'verifyPeer' => $verifyPeer,
        'verifyPeerName' => $verifyPeerName,
    ];

    if (is_string($peerName) && '' !== trim($peerName)) {
        $tls['peerName'] = trim($peerName);
    }

    return [
        'host' => 'epp-test.rnids.rs',
        'password' => $password,
        'port' => 700,
        'tls' => $tls,
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
    $config = clientConfig();
    $tlsConfig = $config['tls'];

    fwrite(
        STDERR,
        sprintf(
            "TLS debug: cert=%s ca=%s allowSelfSigned=%s verifyPeer=%s verifyPeerName=%s peerName=%s\n",
            $tlsConfig['clientCertificatePath'],
            $tlsConfig['caFilePath'],
            $tlsConfig['allowSelfSigned'] ? 'true' : 'false',
            $tlsConfig['verifyPeer'] ? 'true' : 'false',
            $tlsConfig['verifyPeerName'] ? 'true' : 'false',
            isset($tlsConfig['peerName']) ? (string) $tlsConfig['peerName'] : '<default-host>',
        ),
    );

    $client = new RNIDS\Client($config);
    $response = $client->domain()->info($domainName);

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (\Throwable $exception) {
    fail(
        sprintf('domain:info failed: %s', $exception->getMessage()),
    );
}
