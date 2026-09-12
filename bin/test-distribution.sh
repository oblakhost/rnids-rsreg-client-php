#!/usr/bin/env bash
set -euo pipefail

project_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
scratch=$(mktemp -d "${TMPDIR:-/tmp}/rnids-distribution.XXXXXXXX")
trap 'rm -rf -- "$scratch"' EXIT
cd -- "$project_root"

export COMPOSER_DISABLE_NETWORK=1
bash bin/build-distribution.sh "$scratch/release.zip"
bash bin/build-distribution.sh "$scratch/repeated.zip"
cmp "$scratch/release.zip" "$scratch/repeated.zip"
composer archive --format=zip --dir="$scratch" --file=composer --no-interaction --no-plugins --no-scripts

for archive in release composer; do
    package="$scratch/$archive/package"
    consumer="$scratch/$archive/consumer"
    mkdir -p -- "$package" "$consumer"
    unzip -q "$scratch/$archive.zip" -d "$package"
    cp -f tests/Distribution/composer.json "$consumer/composer.json"
    (
        cd -- "$package"
        find . -type f -exec sha256sum {} + | LC_ALL=C sort > "$scratch/$archive-files"

        # Install as a consumer so the SDK's require-dev dependencies are not
        # resolved. Packagist is disabled in the fixture as well as globally.
        cd -- "$consumer"
        composer install --no-dev --no-interaction --no-progress --no-plugins --no-scripts --quiet
        php "$project_root/tests/Distribution/smoke.php" "$package" "$consumer"
    )
done

diff -u "$scratch/release-files" "$scratch/composer-files"
printf 'Both archives contain identical distribution files.\n'
