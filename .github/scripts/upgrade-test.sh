#!/usr/bin/env bash
# Upgrade a 4.5.3 database to the packaged release and check nothing was lost
# (#1684).
#
#   1. Restore the 4.5.3 fixture (src/tests/Fixtures/upgrade/) into the test
#      database and snapshot what it holds.
#   2. Unpack the release zip, give it a config.php pointing at that database,
#      and serve it with PHP's built-in server.
#   3. Make the first web request, so DatabaseMigrations upgrades the schema.
#   4. Check the upgrade over HTTP and in the database: no 5xx, every
#      migration ran, local users log in with their 4.5.3 passwords and are
#      re-hashed, BMLT-shaped rows are converted, volunteers / groups /
#      call handling / service body settings read back unchanged, reports and
#      voicemail return the historical calls.
#   5. Run the fake-Twilio simulator against the upgraded instance.
#
# The database comes from src/config.test.php (the same file the simulator
# reads), so the package, the checks and yap:simulate all use one database.
# This drops and recreates that database.
#
# Run from the repo root after composer install.
# usage: upgrade-test.sh <package.zip | extracted-package-dir>
#
# Environment:
#   UPGRADE_FIXTURE  dump to restore (default src/tests/Fixtures/upgrade/yap-4.5.3.sql.gz)
#   UPGRADE_PORT     port for the packaged app (default 8100)
#   UPGRADE_WORK_DIR where to unpack and keep logs (default: a temp dir)
#   SKIP_SIMULATOR   set to 1 to skip step 5
#   SIMULATOR_SERVICE_BODY  service body yap:simulate routes to and seeds
#                    (default 1006 on latest.aws.bmlt.app). The dump's own
#                    settings for it, or for its parents, change the call flow
#                    the scenarios expect.
set -euo pipefail

TARGET="${1:?usage: upgrade-test.sh <package.zip | extracted-package-dir>}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SRC="$REPO_ROOT/src"
UPGRADE_DIR="$SRC/tests/Upgrade"
FIXTURE="${UPGRADE_FIXTURE:-$SRC/tests/Fixtures/upgrade/yap-4.5.3.sql.gz}"
PORT="${UPGRADE_PORT:-8100}"
SIM_SB="${SIMULATOR_SERVICE_BODY:-1006}"
BASE_URL="http://127.0.0.1:${PORT}"
WORK_DIR="${UPGRADE_WORK_DIR:-$(mktemp -d "${RUNNER_TEMP:-${TMPDIR:-/tmp}}/yap-upgrade.XXXXXX")}"
mkdir -p "$WORK_DIR"
TARGET="$(cd "$(dirname "$TARGET")" && pwd)/$(basename "$TARGET")"

echo "== work dir: $WORK_DIR"

# --- 1. restore the 4.5.3 database --------------------------------------------
"$UPGRADE_DIR/restore-fixture.sh" "$FIXTURE"

echo "== snapshot the 4.5.3 data"
php "$UPGRADE_DIR/upgrade-check.php" snapshot "$WORK_DIR/before.json"

# --- 2. unpack and configure the package --------------------------------------
if [[ -f "$TARGET" ]]; then
    rm -rf "$WORK_DIR/package-unzip"
    unzip -q "$TARGET" -d "$WORK_DIR/package-unzip"
    PACKAGE_DIR="$(find "$WORK_DIR/package-unzip" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
elif [[ -d "$TARGET" ]]; then
    PACKAGE_DIR="$WORK_DIR/package"
    rm -rf "$PACKAGE_DIR"
    cp -R "$TARGET" "$PACKAGE_DIR"
else
    echo "upgrade-test.sh: $TARGET is not a zip or a directory" >&2
    exit 2
fi
if [[ -e "$PACKAGE_DIR/config.php" ]]; then
    echo "upgrade-test.sh: the package ships a config.php" >&2
    exit 1
fi
# The simulator signs webhooks with config.test.php's Twilio token, so the
# package gets the same settings (database, Twilio test credentials, BMLT).
cp "$SRC/config.test.php" "$PACKAGE_DIR/config.php"
echo "== package: $PACKAGE_DIR"

# --- 3. serve it and make the first request -----------------------------------
SERVER_LOG="$WORK_DIR/server.log"
(
    cd "$PACKAGE_DIR"
    # Fake Twilio mode is refused when APP_ENV is production (the default).
    # E2E_TESTING relaxes the 5/min login throttle; the checks log in once per
    # user. SESSION_DRIVER=file keeps call state between simulator webhooks.
    exec env -u ENVIRONMENT \
        APP_ENV=local TWILIO_FAKE_MODE=true TWILIO_DISABLE_SIGNATURE_VALIDATION=false \
        SESSION_DRIVER=file APP_URL="$BASE_URL" E2E_TESTING=1 \
        php -d variables_order=EGPCS -S "127.0.0.1:${PORT}" index.php
) > "$SERVER_LOG" 2>&1 &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true' EXIT

# Wait for the port without sending a request: the checker's request must be
# the first one the app sees.
for _ in $(seq 1 30); do
    if php -r 'exit(@fsockopen("127.0.0.1", (int) $argv[1]) ? 0 : 1);' "$PORT"; then break; fi
    sleep 1
done

# --- 4. check the upgrade -----------------------------------------------------
# The checker makes the first request itself, so it can see the schema
# before and after it.
php "$UPGRADE_DIR/upgrade-check.php" check "$WORK_DIR/before.json" "$BASE_URL" "$PACKAGE_DIR"

# --- 5. simulator -------------------------------------------------------------
if [[ "${SKIP_SIMULATOR:-0}" != "1" ]]; then
    echo "== simulator against the upgraded instance"
    (
        cd "$SRC"
        # --seed is refused when APP_ENV is production; there is no .env here.
        export ENVIRONMENT=test APP_ENV=local
        php artisan yap:simulate -v --base-url="$BASE_URL" --service-body="$SIM_SB" --seed
        php artisan yap:simulate -v helpline-volunteer --base-url="$BASE_URL" --service-body="$SIM_SB" --seed --outcome=no-answer
    )
fi

# No request, including the simulator's, may have returned a 5xx. PHP's
# built-in server logs each request as "... 127.0.0.1:54321 [500]: GET /path".
if grep -E '\[5[0-9]{2}\]: ' "$SERVER_LOG"; then
    echo "upgrade-test.sh: the packaged app returned a 5xx (see $SERVER_LOG)" >&2
    exit 1
fi

echo "Upgrade test passed"
