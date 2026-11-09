#!/usr/bin/env bash
# Restore an upgrade fixture (a gzipped mysqldump, see
# tests/Fixtures/upgrade/README.md) into the database named in
# config.test.php. The database is dropped and recreated first.
#
# MySQL 8 writes utf8mb4_0900_ai_ci collations that MariaDB before 11.4 does
# not know, so they are rewritten to utf8mb4_unicode_ci (what yap's
# config/database.php uses anyway).
#
# usage: restore-fixture.sh <dump.sql.gz> [database]
set -euo pipefail

FIXTURE="${1:?usage: restore-fixture.sh <dump.sql.gz> [database]}"
HERE="$(cd "$(dirname "$0")" && pwd)"
eval "$(php "$HERE/db-env.php")"
DATABASE="${2:-$DB_DATABASE}"

export MYSQL_PWD="$DB_PASSWORD"
MYSQL=(mysql --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --default-character-set=utf8mb4)

echo "== restore $(basename "$FIXTURE") into $DATABASE on $DB_HOST:$DB_PORT"
"${MYSQL[@]}" -e "SELECT VERSION() AS server_version;
DROP DATABASE IF EXISTS \`$DATABASE\`;
CREATE DATABASE \`$DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

gzip -dc "$FIXTURE" \
    | sed -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' \
    | "${MYSQL[@]}" "$DATABASE"
