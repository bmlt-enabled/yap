# 4.5.3 upgrade fixture

`yap-4.5.3.sql.gz` is a gzipped `mysqldump` of a yap **4.5.3** database. The
`upgrade` CI job (`.github/scripts/upgrade-test.sh`, bmlt-enabled/yap#1684)
restores it into MySQL 8.0 and MariaDB 10.3 / 10.6 / 11.4, boots the packaged
release zip against it, and checks that:

- the first web request runs every pending migration without a 5xx;
- local users log in with their 4.5.3 passwords and are re-hashed from SHA-256;
- the `auth_source` migration (#1676) converts the BMLT-shaped rows (and no others);
- volunteers, group volunteers, groups, call handling, service body settings and
  users read back unchanged through the admin API;
- the CDR, metrics summary and voicemail reports return the historical calls,
  with the numbers 4.5.3's own report SQL gives for the same data;
- the fake-Twilio simulator works against the upgraded instance.

Every expectation comes from the dump itself (`upgrade-check.php snapshot`
reads it before any 5.0 code runs), so nothing in the checks is tied to a
particular dump.

## The committed file is synthetic, on purpose

The committed file is a **stand-in**: 4.5.3's own migrations run against an
empty database, then filled with invented data in 4.5.3's formats by
`tests/Upgrade/seed-standin.php` (see [Rebuilding the
stand-in](#rebuilding-the-stand-in)). It has no pre-Laravel schema, no data
written by older 4.x versions, and none of the oddities a database collects
over years.

**Never commit a dump of a real install here, even a sanitized one.**
Sanitizing hides people, not the helpline: call times, caller states and
rounded caller locations still say whose database it was, and anything the
sanitizer misses is public for good. Test real installs locally instead
(below), and turn what they teach into seed data or sanitizer rules.

## Testing a real install locally

To check that a real 4.5.x database upgrades cleanly, run the same test
against a sanitized copy that never leaves your machine.

1. Take a dump of the real install. Do this on a copy, never on the live
   server. A phpMyAdmin export works too; its `START TRANSACTION` / `COMMIT`
   are fine:

   ```sh
   mysqldump --single-transaction --no-tablespaces <yap_db> > "$TMPDIR/real-4.5.x.sql"
   ```

   The raw dump holds callers' and volunteers' numbers, names and password
   hashes. Keep it outside the repo tree, and print only counts and shapes
   while you work with it.

2. Restore it into a **scratch** database on a throwaway local server. The
   scripts read the host, port, user and password from `src/config.test.php`,
   so point that at your local server first (and don't commit that change):

   ```sh
   mysql -e 'CREATE DATABASE yap_real_453 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
   mysql yap_real_453 < "$TMPDIR/real-4.5.x.sql"
   ```

   The MySQL 9 client cannot log in to MariaDB, since it dropped
   `mysql_native_password`. Use an 8.x client (for example Homebrew's
   keg-only `mysql-client@8.4`, first on `PATH`), which is also what CI's
   `mysqldump` is.

3. Sanitize it and write the dump **outside the repo** (run from `src/`):

   ```sh
   tests/Upgrade/export-fixture.sh yap_real_453 "$TMPDIR/real-sanitized.sql.gz"
   ```

   `export-fixture.sh` runs `sanitize-database.php` against the scratch
   database **in place**, then writes the dump. Sanitizing is what the
   checks' logins rely on (every local user gets a known password). The
   sanitizer stops if anything still looks like a phone number, an email or
   a URL on a non-Twilio host afterwards, and lists each one by
   `table.column` and a masked shape (`+99999999999`), never the value. When
   it stops on a shape it did not expect, fix the sanitizer and rerun.

4. Run the upgrade test (below) with
   `UPGRADE_FIXTURE="$TMPDIR/real-sanitized.sql.gz"`.

5. Delete the raw dump, the sanitized dump, the scratch database and the
   server when you are done.

### What the sanitizer changes

`tests/Upgrade/sanitize-database.php` keeps every table, row count and row
shape, and changes:

| Data | Becomes |
|---|---|
| `users.username` / `name` | `user<id>` / `User <id>`. When name equalled username it stays equal, so BMLT-shaped rows keep that shape. When they were equal but for case (`Admin` / `admin`) they become `User<id>` / `user<id>`, which the column's case-insensitive collation still calls equal. |
| `users.password` (non-empty) | SHA-256 of `yap-upgrade-<new username>`. This is the contract with the checks, which log in with exactly that. Empty stays empty. |
| Phone numbers (columns, JSON fields, free text, URL-encoded) | Fictional `555` numbers, keeping punctuation and length. One number maps to the same fake everywhere in the run. `0000000000` (unknown) is kept. |
| Emails | `person<n>@example.invalid` |
| Twilio SIDs (`AC…`, `CA…`, `RE…`, …) | Fake SIDs, mapped consistently, so callsids still join `records`, `records_events`, `conference_participants`, `sessions` and `event_status`. |
| Volunteer, group, caller and participant names | `Person <n>`, `Group <n>`, … |
| Helpline titles (`title` in service body settings) | `Helpline <n>` |
| URLs on any host but Twilio's (a custom greeting or hold music on the install's own site) | `https://example.invalid/sanitized.<ext>`, keeping the file extension |
| Volunteer notes, SMS bodies, transcriptions | `Sanitized Notes` / `Sanitized Message` / … |
| Caller city / address / gathered location, zip | `Anytown`, `00000` |
| Coordinates | Rounded to 1 decimal place (~10 km) |
| Credentials in settings (`*token`, `*password`, `*secret`, `*api_key`) | `redacted` |
| `cache` | emptied |

The mapping key is random for each run and never stored.

The dump **must keep at least one local `is_admin = 1` user**. The checks read
the admin API as that user. The installer's `admin` row qualifies.

## Running the upgrade test locally

You need a MySQL or MariaDB server, matching `src/config.test.php`. The test
drops and recreates that database. You also need `composer install` in `src/`
and the `mysql` client.

```sh
# from the repo root
.github/scripts/build-package.sh yap-smoke        # needs Node 24 for the webpack build
.github/scripts/upgrade-test.sh src/yap-smoke.zip
```

`UPGRADE_FIXTURE=<file>` tests another dump, `SKIP_SIMULATOR=1` skips the
simulator (it calls latest.aws.bmlt.app), `SIMULATOR_SERVICE_BODY` changes the
service body the simulator routes to (default `1006`; a dump with its own
settings for 1006 changes the call flow the scenarios expect), and
`UPGRADE_WORK_DIR` keeps the
unpacked package, `server.log` and `before.json` somewhere you choose. Login
also calls the BMLT root server named in `config.test.php`, so the test needs
network access.

## Rebuilding the stand-in

```sh
cd src && tests/Upgrade/build-standin-fixture.sh [scratch_db] [output.sql.gz]
```

The output defaults to the committed `yap-4.5.3.sql.gz`.

This checks out tag `4.5.3` into a temp dir with a throwaway git index, so no
worktree or ref is touched. It then runs `composer install` and 4.5.3's
`php artisan migrate` into the scratch database, seeds it, and exports it
through `export-fixture.sh`. That is the same sanitize-and-dump path a real
dump takes. Use `COMPOSER_BIN` to point at a composer 2 binary.

## Files

| File | Purpose |
|---|---|
| `yap-4.5.3.sql.gz` | The fixture. Replace this file to change the dump. |
| `tests/Upgrade/restore-fixture.sh` | Restores a fixture. It rewrites MySQL 8's `utf8mb4_0900_ai_ci` collation, which MariaDB before 11.4 lacks. |
| `tests/Upgrade/upgrade-check.php` | `snapshot` (before the upgrade) and `check` (after). |
| `tests/Upgrade/sanitize-database.php` | The sanitizer. |
| `tests/Upgrade/export-fixture.sh` | Sanitizes, then runs `mysqldump`, then gzips. |
| `tests/Upgrade/build-standin-fixture.sh`, `seed-standin.php` | The synthetic stand-in. |
| `.github/scripts/upgrade-test.sh` | Runs the whole test, as CI does. |
