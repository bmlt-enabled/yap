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

## ⚠️ The current file is SYNTHETIC

The file committed today is a **stand-in**: 4.5.3's own migrations run against
an empty database, then filled with invented data in 4.5.3's formats by
`tests/Upgrade/seed-standin.php`. It proves the pipeline. It is **not** a
real install: it has no pre-Laravel schema, no data written by older 4.x
versions, and none of the oddities a database collects over years. Replace it
with a sanitized dump of a real 4.5.3 install before treating #1684 as done.

## Replacing it with a real 4.5.3 dump

Swapping the dump changes this one file. Nothing else needs editing.

1. Take a dump of the real install. Do this on a copy, never on the live
   server:

   ```sh
   mysqldump --single-transaction --no-tablespaces <yap_db> > real-4.5.3.sql
   ```

2. Restore it into a **scratch** database on a local server. The scripts
   read the host, port, user and password from `src/config.test.php`, so
   point that at your local server first:

   ```sh
   mysql -e 'CREATE DATABASE yap_real_453 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
   mysql yap_real_453 < real-4.5.3.sql
   ```

3. Sanitize it and write the fixture (run from `src/`):

   ```sh
   tests/Upgrade/export-fixture.sh yap_real_453
   ```

   `export-fixture.sh` runs `sanitize-database.php` against the scratch database
   **in place**, then writes `tests/Fixtures/upgrade/yap-4.5.3.sql.gz`. The
   sanitizer stops with a list of what it found if anything still looks like a
   phone number or an email afterwards.

4. Look at the result before committing it. For example,
   `gzip -dc yap-4.5.3.sql.gz | less`. Check names, notes, settings and
   anything specific to the install that the sanitizer can't know about, such
   as a helpline title that names a person.

5. Run the upgrade test locally (below), then commit the new `.sql.gz`, update
   the "SYNTHETIC" section above, and remove the warning from the PR or release
   notes.

### What the sanitizer changes

`tests/Upgrade/sanitize-database.php` keeps every table, row count and row
shape, and changes:

| Data | Becomes |
|---|---|
| `users.username` / `name` | `user<id>` / `User <id>`. When name equalled username it stays equal, so BMLT-shaped rows keep that shape. |
| `users.password` (non-empty) | SHA-256 of `yap-upgrade-<new username>`. This is the contract with the checks, which log in with exactly that. Empty stays empty. |
| Phone numbers (columns, JSON fields, free text, URL-encoded) | Fictional `555` numbers, keeping punctuation and length. One number maps to the same fake everywhere in the run. `0000000000` (unknown) is kept. |
| Emails | `person<n>@example.invalid` |
| Twilio SIDs (`AC…`, `CA…`, `RE…`, …) | Fake SIDs, mapped consistently, so callsids still join `records`, `records_events`, `conference_participants`, `sessions` and `event_status`. |
| Volunteer, group, caller and participant names | `Person <n>`, `Group <n>`, … |
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
cd src && tests/Upgrade/build-standin-fixture.sh [scratch_db]
```

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
