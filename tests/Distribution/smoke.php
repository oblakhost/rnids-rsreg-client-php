<?php

declare(strict_types=1);

// Run against an extracted, production-only Composer installation.
$packageRoot = $argv[1] ?? '';
$consumerRoot = $argv[2] ?? '';

try {
    foreach ([
        'composer.json',
        'LICENSE',
        'README.md',
        'CONTRIBUTING.md',
        'SUPPORT.md',
        'SECURITY.md',
        'UPGRADING.md',
        'NOTICE',
        'bin/rsreg',
        'docs/api-reference.md',
        'docs/cli.md',
    ] as $file) {
        if (!is_file($packageRoot . '/' . $file)) {
            throw new RuntimeException('Missing distributed file: ' . $file);
        }
    }

    foreach (['tests', 'old-client', '.github', '.beads', '.git', 'composer.lock', 'docs/audits'] as $path) {
        if (file_exists($packageRoot . '/' . $path)) {
            throw new RuntimeException('Development state was distributed: ' . $path);
        }
    }

    require $consumerRoot . '/vendor/autoload.php';

    if (!class_exists(RNIDS\Client::class)) {
        throw new RuntimeException('The installed package cannot autoload RNIDS\\Client.');
    }

    // Do not inherit credentials or certificate paths, even on a developer's machine.
    $environment = getenv();

    foreach (array_keys($environment) as $name) {
        if (str_starts_with($name, 'RNIDS_')) {
            unset($environment[$name]);
        }
    }

    $process = proc_open(
        [$consumerRoot . '/vendor/bin/rsreg', '--help'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $consumerRoot,
        $environment,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Could not start the distributed CLI.');
    }

    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0 || !str_contains($output, 'Usage:')) {
        throw new RuntimeException(sprintf('Distributed CLI help failed (%d): %s%s', $status, $output, $errors));
    }

    echo "Distribution autoload and CLI smoke check passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
