#!/usr/bin/env bash
# Build the release zip the way it ships: git archive (honouring
# src/.gitattributes export-ignore), plus built front-end assets and a
# production-only vendor/ (no require-dev packages, #1702).
#
# Run from the repo root after composer install. Writes src/<name>.zip and
# leaves the unpacked tree at src/<name>/.
set -euo pipefail

ARTIFACT_FILE="${1:?usage: build-package.sh <artifact-name>}"
ARTIFACT_FILENAME="${ARTIFACT_FILE}.zip"

cd "$(dirname "$0")/../../src"

git archive --format=zip --output="${ARTIFACT_FILENAME}" --prefix="${ARTIFACT_FILE}/" HEAD
DISABLE_NOTIFIER=true make deploy
unzip -q "${ARTIFACT_FILENAME}"
rm "${ARTIFACT_FILENAME}"
echo "${GITHUB_SHA:-$(git rev-parse HEAD)}" > "${ARTIFACT_FILE}/storage/app/build.txt"
cp -R vendor "${ARTIFACT_FILE}/"
# The checkout's vendor/ has the dev packages (phpunit, pest, phpcs, ...) the
# CI jobs need. Prune them from the package's copy only (#1702): composer
# removes what require-dev pulled in and rewrites the autoloader without them.
# Everything it keeps is already in vendor/, so nothing is downloaded.
#   --no-scripts: package:discover needs artisan, which the zip leaves out; the
#     app builds its package manifest at runtime from vendor/composer/installed.json.
#   --no-plugins: the only plugin (pest) is itself a dev package being removed.
#   Throwaway COMPOSER_HOME, no COMPOSER_AUTH: the runner's Composer rejects the
#     GitHub token setup-php injects ("contains invalid characters"), and this
#     install never talks to GitHub.
cp composer.json composer.lock "${ARTIFACT_FILE}/"
COMPOSER_PRUNE_HOME="$(mktemp -d "${RUNNER_TEMP:-${TMPDIR:-/tmp}}/yap-composer.XXXXXX")"
(
    cd "${ARTIFACT_FILE}"
    env -u COMPOSER_AUTH -u GITHUB_TOKEN \
        COMPOSER_HOME="${COMPOSER_PRUNE_HOME}" COMPOSER_CACHE_DIR="${COMPOSER_PRUNE_HOME}/cache" \
        COMPOSER_NO_INTERACTION=1 \
        composer install --no-dev --no-scripts --no-plugins --no-progress \
            --optimize-autoloader --ignore-platform-req=php
    rm composer.json composer.lock
)
rm -rf "${COMPOSER_PRUNE_HOME}"
if [[ -d "${ARTIFACT_FILE}/vendor/squizlabs" || -d "${ARTIFACT_FILE}/vendor/phpunit" ]]; then
    echo "build-package.sh: dev packages are still in ${ARTIFACT_FILE}/vendor" >&2
    exit 1
fi
cp ../LICENSE .
cp ../RELEASENOTES.md .
mkdir -p "${ARTIFACT_FILE}/public" && cp -R public/. "${ARTIFACT_FILE}/public"
find "./${ARTIFACT_FILE}" -type d -print0 | xargs -0 chmod 755
find "./${ARTIFACT_FILE}" -name '*.php' -print0 | xargs -0 chmod 644
zip -q -r -9 "${ARTIFACT_FILENAME}" "${ARTIFACT_FILE}"
