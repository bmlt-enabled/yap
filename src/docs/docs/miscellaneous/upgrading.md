---
title: Upgrading
sidebar_position: 7
---

# Upgrading from Yap 4.x to Yap 5.x

See [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) for release-critical changes in 5.0 (Twilio signature validation, `TRUSTED_PROXIES`, and related breaking changes).

Upgrading to Yap 5.0 includes a destructive UUID migration that is **blocked** until a server administrator applies it. Safe schema migrations may run automatically on the first HTTP request; the UUID conversion returns HTTP 503 until the destructive migration completes. Run upgrade advisor checks **before** pointing traffic at the new folder.

## Step 1: Run upgrade advisor checks against your 4.5.x database

1. Create a new folder with the Yap 5.0 code.
2. Copy `config.php` from your existing 4.5.x install into the new folder.
3. Point a **staging URL** or temporary vhost at the new folder (or ask your host to do this) so you can reach Yap over HTTPS without sending live Twilio traffic there yet.
4. Open the upgrade advisor in your browser:

```
https://your-staging-host/api/v1/upgrade
```

Or log in to the admin portal and open **Dashboard** or **System Health** — both show the same `checks` list from the upgrade advisor.

The upgrade advisor validates your database and environment over HTTP. It reports blocking issues and remediation guidance for each check.

Upgrade advisor checks include:

- Required `config.php` settings
- Duplicate or empty `users.username` values (UUID migration blockers)
- `users` primary key and `username` unique index shape
- `twilio_auth_token` is present (empty token rejects every IVR call with HTTP 403)
- `TRUSTED_PROXIES` when behind a reverse proxy (warning when unset)
- `SESSION_DRIVER` is not `database` (Yap's `sessions` table stores call PINs, not Laravel sessions)
- Required PHP extensions (`fileinfo`, `pdo_mysql`, `curl`, etc.) — see [PHP requirements](../general/php-requirements)
- `APP_ENV` value (several guards compare against the exact string `production`)
- MySQL and PHP versions against Yap 5.0 requirements

Fix every check with status `fail` before continuing.

## Step 2: Deploy the new folder

Once upgrade advisor checks pass, copy over any other local customizations, update your web server to point at the new folder, and monitor the first requests.

## Step 3: Confirm with the upgrade advisor after deploy

After the upgrade, open the upgrade advisor again to confirm runtime settings:

```
https://your-yap-host/api/v1/upgrade
```

The response includes a `checks` array (with `status` values of `pass`, `warn`, `fail`, or `skip`), plus root-server, Google Maps, Twilio webhook, and **Twilio compliance** validations (account type, US voice geo permissions, Trust Hub profile, A2P SMS brand registration, and toll-free verification). The admin **Dashboard** and **System Health** pages show the same checks after you log in.

## General upgrade notes

Make a new folder with the newer version and copy over `config.php`. Once you are comfortable you can delete the older folder and rename the new one.

For upgrades from older major versions, see the dedicated guides in this section.
