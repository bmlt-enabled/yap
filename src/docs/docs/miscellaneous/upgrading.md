---
title: Upgrading
sidebar_position: 7
---

# Upgrading from Yap 4.x to Yap 5.x

See [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) for release-critical changes in 5.0 (Twilio signature validation, `TRUSTED_PROXIES`, and related breaking changes).

Upgrading to Yap 5.0 includes a destructive UUID migration that is **blocked** until you run `php artisan migrate` manually. Safe schema migrations may run automatically on the first HTTP request; the UUID conversion returns HTTP 503 until you migrate. Run preflight checks **before** pointing traffic at the new folder.

## Step 1: Run preflight against your 4.5.x database

1. Create a new folder with the Yap 5.0 code.
2. Copy `config.php` from your existing 4.5.x install into the new folder.
3. From the new folder, run:

```bash
cd src
php artisan yap:preflight
```

This command validates your database and environment **without booting the web stack**. It exits with a non-zero status when any blocking issue is found and prints remediation guidance for each check.

Preflight validates:

- Required `config.php` settings
- Duplicate or empty `users.username` values (UUID migration blockers)
- `users` primary key and `username` unique index shape
- `twilio_auth_token` is present (empty token rejects every IVR call with HTTP 403)
- `TRUSTED_PROXIES` when behind a reverse proxy (warning when unset)
- `SESSION_DRIVER` is not `database` (Yap's `sessions` table stores call PINs, not Laravel sessions)
- Required PHP extensions (`fileinfo`, `pdo_mysql`, `curl`, etc.) — see [PHP requirements](../general/php-requirements)
- `APP_ENV` value (several guards compare against the exact string `production`)
- MySQL and PHP versions against Yap 5.0 requirements

Fix every `[FAIL]` result before continuing.

## Step 2: Deploy the new folder

Once preflight passes, copy over any other local customizations, update your web server to point at the new folder, and monitor the first requests.

## Step 3: Confirm with the upgrade advisor

After the upgrade, call the upgrade advisor to confirm runtime settings:

```bash
curl https://your-yap-host/api/v1/upgrade
```

The response includes the same `checks` array as `php artisan yap:preflight` (with `status` values of `pass`, `warn`, `fail`, or `skip`), plus root-server, Google Maps, Twilio webhook, and **Twilio compliance** validations (account type, US voice geo permissions, Trust Hub profile, A2P SMS brand registration, and toll-free verification).

## General upgrade notes

Make a new folder with the newer version and copy over `config.php`. Once you are comfortable you can delete the older folder and rename the new one.

For upgrades from older major versions, see the dedicated guides in this section.
