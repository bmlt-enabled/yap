---
title: Upgrading
sidebar_position: 7
---

# Upgrading from Yap 4.x to Yap 5.x

See [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) for release-critical changes in 5.0 (Twilio signature validation, `TRUSTED_PROXIES`, and related breaking changes).

Upgrading to Yap 5.0 includes a destructive UUID migration that is **blocked** until a server administrator completes the database upgrade step. Safe schema migrations may run automatically on the first HTTP request; the UUID conversion shows a **Database Upgrade Required** page until that step is finished. Run the upgrade advisor **before** pointing traffic at the new folder.

## Step 1: Run the upgrade advisor against your 4.5.x database

1. Create a new folder with the Yap 5.0 code.
2. Copy `config.php` from your existing 4.5.x install into the new folder.
3. Point your web server at the new folder temporarily (or use a staging URL) so the upgrade advisor can reach your database through `config.php`.
4. Open the **upgrade advisor** in your browser:

   `https://your-yap-host/api/v1/upgrade`

   Or log into the admin portal at `/admin` and review the system status on the **Dashboard**.

The upgrade advisor validates your database and environment over HTTP. When `status` is `false`, read the `checks` array — each failed check includes remediation text. Fix every blocking failure before going live.

The advisor checks:

- Required `config.php` settings
- Duplicate or empty `users.username` values (UUID migration blockers)
- `users` primary key and `username` unique index shape
- `twilio_auth_token` is present (empty token rejects every IVR call with HTTP 403)
- `TRUSTED_PROXIES` when behind a reverse proxy (warning when unset)
- `SESSION_DRIVER` is not `database` (Yap's `sessions` table stores call PINs, not Laravel sessions)
- Required PHP extensions (`fileinfo`, `pdo_mysql`, `curl`, etc.) — see [PHP requirements](../general/php-requirements)
- `APP_ENV` value (several guards compare against the exact string `production`)
- MySQL and PHP versions against Yap 5.0 requirements

## Step 2: Deploy the new folder

Once the upgrade advisor reports no blocking failures, copy over any other local customizations, update your web server to point at the new folder for production traffic, and monitor the first requests.

## Step 3: Confirm after the upgrade

Re-open the upgrade advisor (`/api/v1/upgrade`) or refresh the admin **Dashboard**. The response includes the same `checks` array plus root-server, Google Maps, and Twilio webhook validations.

## General upgrade notes

Make a new folder with the newer version and copy over `config.php`. Once you are comfortable you can delete the older folder and rename the new one.

For upgrades from older major versions, see the dedicated guides in this section.
