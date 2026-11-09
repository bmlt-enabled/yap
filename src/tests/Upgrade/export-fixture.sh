#!/usr/bin/env bash
# Sanitize a scratch copy of a 4.5.x database and write it as the upgrade-test
# fixture (#1684). See tests/Fixtures/upgrade/README.md.
#
# NEVER point this at a live database: sanitize-database.php rewrites every
# row of <database> in place.
#
# usage: export-fixture.sh <database> [output.sql.gz]
#   default output: tests/Fixtures/upgrade/yap-4.5.3.sql.gz
set -euo pipefail

DATABASE="${1:?usage: export-fixture.sh <database> [output.sql.gz]}"
HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="${2:-$HERE/../Fixtures/upgrade/yap-4.5.3.sql.gz}"
eval "$(php "$HERE/db-env.php")"

php "$HERE/sanitize-database.php" "$DATABASE"

export MYSQL_PWD="$DB_PASSWORD"
DUMP_OPTS=(--protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER"
    --single-transaction --skip-comments --skip-dump-date --no-tablespaces
    --skip-add-locks --default-character-set=utf8mb4 --hex-blob --skip-triggers)
# mysqldump 8 needs these off against MariaDB / without GTIDs; MariaDB's
# mysqldump does not know them.
# (Read the help into a variable: grep -q in a pipe would SIGPIPE mysqldump
# and trip pipefail.)
DUMP_HELP="$(mysqldump --help 2>/dev/null || true)"
if [[ "$DUMP_HELP" == *--set-gtid-purged* ]]; then
    DUMP_OPTS+=(--set-gtid-purged=OFF)
fi
if [[ "$DUMP_HELP" == *--column-statistics* ]]; then
    DUMP_OPTS+=(--column-statistics=0)
fi

# gzip -n: no name or timestamp in the header, so re-exporting identical data
# gives an identical file.
mysqldump "${DUMP_OPTS[@]}" "$DATABASE" | gzip -9 -n > "$OUT"
echo "Wrote $OUT ($(wc -c < "$OUT" | tr -d ' ') bytes)"
