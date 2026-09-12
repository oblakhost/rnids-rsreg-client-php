#!/usr/bin/env bash
set -euo pipefail

project_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
cd -- "$project_root"

archive_path=${1:-build/release.zip}
commit=$(git rev-parse --verify "${2:-HEAD}^{commit}")
mkdir -p -- "$(dirname -- "$archive_path")"

# A commit fixes both file content and timestamps. Git and Composer use the
# same export-ignore policy; local, uncommitted files never enter a release.
git archive --format=zip --output="$archive_path" "$commit"
printf 'Built %s from %s\n' "$archive_path" "$commit"
