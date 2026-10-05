#!/usr/bin/env bash
# Usage: ./run.sh [redocly-cli-version]   (default: latest)
set -euo pipefail
cd "$(dirname "$0")"

rm -rf out
npx --yes "@redocly/cli@${1:-latest}" generate-client repro --config ./redocly.yaml

echo
echo '--- Generated operation map and lookup ---'
grep -nE '^const OPERATIONS|OPERATIONS\[' out/client.php

echo
echo '--- Without preload ---'
php -d opcache.enable_cli=1 request.php

echo
echo '--- With opcache.preload ---'
php -d opcache.enable_cli=1 -d opcache.preload="$PWD/preload.php" request.php
