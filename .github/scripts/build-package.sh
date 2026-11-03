#!/usr/bin/env bash
# Build the release zip the way it ships: git archive (honouring
# src/.gitattributes export-ignore), plus built front-end assets and vendor/.
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
cp ../LICENSE .
cp ../RELEASENOTES.md .
mkdir -p "${ARTIFACT_FILE}/public" && cp -R public/. "${ARTIFACT_FILE}/public"
find "./${ARTIFACT_FILE}" -type d -print0 | xargs -0 chmod 755
find "./${ARTIFACT_FILE}" -name '*.php' -print0 | xargs -0 chmod 644
zip -q -r -9 "${ARTIFACT_FILENAME}" "${ARTIFACT_FILE}"
