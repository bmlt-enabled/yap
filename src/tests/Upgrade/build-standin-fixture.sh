#!/usr/bin/env bash
# Build the SYNTHETIC stand-in for the 4.5.3 upgrade fixture (#1684), used
# until a sanitized dump of a real 4.5.3 install replaces it. See
# tests/Fixtures/upgrade/README.md.
#
#   1. Check out tag 4.5.3 (into a temp dir; no refs or worktrees are touched)
#      and composer install it.
#   2. Run 4.5.3's own migrations against an empty scratch database.
#   3. Fill it with representative fake data written in 4.5.3's formats
#      (seed-standin.php).
#   4. Sanitize and dump it with export-fixture.sh, the same path a real dump
#      takes, so the stand-in has the exact shape a real fixture will have.
#
# The scratch database is on the server config.test.php names. It is dropped
# and recreated.
#
# usage: build-standin-fixture.sh [scratch-database] [output.sql.gz]
#   needs: git, composer (2.x), php, mysql + mysqldump clients
set -euo pipefail

SCRATCH_DB="${1:-yap_upgrade_standin}"
HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="${2:-$HERE/../Fixtures/upgrade/yap-4.5.3.sql.gz}"
TAG="${STANDIN_TAG:-4.5.3}"
COMPOSER="${COMPOSER_BIN:-composer}"
eval "$(php "$HERE/db-env.php")"

WORK="$(mktemp -d "${TMPDIR:-/tmp}/yap-standin.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT

echo "== check out $TAG into $WORK"
# A throwaway index, so the export-ignore rules in src/.gitattributes (which
# leave artisan and composer.json out of git archive) don't apply.
TOP="$(git -C "$HERE" rev-parse --show-toplevel)"
GIT_INDEX_FILE="$WORK/index" git -C "$TOP" read-tree "$TAG"
GIT_INDEX_FILE="$WORK/index" git -C "$TOP" checkout-index -a --prefix="$WORK/tree/"
APP="$WORK/tree/src"

echo "== composer install ($TAG)"
(cd "$APP" && $COMPOSER install --no-dev --no-progress --no-scripts --no-interaction --ignore-platform-reqs -q)

echo "== create $SCRATCH_DB and run $TAG's migrations"
export MYSQL_PWD="$DB_PASSWORD"
mysql --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e "DROP DATABASE IF EXISTS \`$SCRATCH_DB\`;
CREATE DATABASE \`$SCRATCH_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cat > "$APP/config.php" <<PHP
<?php
static \$title = "Yap 4.5.3 stand-in";
static \$bmlt_root_server = "https://latest.aws.bmlt.app/main_server";
static \$google_maps_api_key = "not-a-real-key";
static \$twilio_account_sid = "AC00000000000000000000000000000000";
static \$twilio_auth_token = "not-a-real-token";
static \$mysql_hostname = "$DB_HOST";
static \$mysql_port = $DB_PORT;
static \$mysql_username = "$DB_USER";
static \$mysql_password = "$DB_PASSWORD";
static \$mysql_database = "$SCRATCH_DB";
PHP
(cd "$APP" && env -u ENVIRONMENT php artisan migrate --force --no-interaction)

echo "== seed fake 4.5.3 data"
php "$HERE/seed-standin.php" "$SCRATCH_DB"

"$HERE/export-fixture.sh" "$SCRATCH_DB" "$OUT"
