#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @return never
 */
function fail(string $message): void
{
    \fwrite(\STDERR, $message . \PHP_EOL);
    exit(1);
}

if ($argc < 3) {
    fail('Usage: php bin/coverage-gate.php <clover-xml-path> <minimum-percent>');
}

$coverageFile = $argv[1];
$minimumPercentRaw = $argv[2];

if (!\is_file($coverageFile) || !\is_readable($coverageFile)) {
    fail(\sprintf('Coverage file is not readable: %s', $coverageFile));
}

if (!\is_numeric($minimumPercentRaw)) {
    fail(\sprintf('Minimum percent must be numeric, got: %s', $minimumPercentRaw));
}

$minimumPercent = (float) $minimumPercentRaw;

if ($minimumPercent < 0 || $minimumPercent > 100) {
    fail('Minimum percent must be between 0 and 100.');
}

$xml = @\simplexml_load_file($coverageFile);

if (false === $xml) {
    fail(\sprintf('Failed to parse coverage XML: %s', $coverageFile));
}

$metrics = null;

if (isset($xml->project->metrics)) {
    $metrics = $xml->project->metrics;
} elseif (isset($xml->metrics)) {
    $metrics = $xml->metrics;
}

if (null === $metrics) {
    fail('Coverage XML does not contain project metrics.');
}

$linesValidRaw = (string) ($metrics['statements'] ?? $metrics['lines-valid'] ?? '');
$linesCoveredRaw = (string) ($metrics['coveredstatements'] ?? $metrics['lines-covered'] ?? '');

if ('' === $linesValidRaw || '' === $linesCoveredRaw) {
    fail('Coverage XML metrics are missing statements/coveredstatements fields.');
}

$linesValid = (float) $linesValidRaw;
$linesCovered = (float) $linesCoveredRaw;

if ($linesValid <= 0) {
    fail('Coverage XML reports zero executable lines.');
}

$coveragePercent = ($linesCovered / $linesValid) * 100;

\fwrite(
    \STDOUT,
    \sprintf(
        'Line coverage: %.2f%% (covered %.0f / total %.0f), threshold %.2f%%' . \PHP_EOL,
        $coveragePercent,
        $linesCovered,
        $linesValid,
        $minimumPercent,
    ),
);

if ($coveragePercent < $minimumPercent) {
    fail(
        \sprintf(
            'Coverage gate failed: %.2f%% is below %.2f%%.',
            $coveragePercent,
            $minimumPercent,
        ),
    );
}
