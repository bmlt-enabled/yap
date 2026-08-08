---
title: Upgrading from Yap 4.x to Yap 5.x
sidebar_position: 6
---

**BACK UP YOUR DATABASE. THIS UPGRADE IS NOT REVERSIBLE.**

Yap 5.0 is a major release for self-hosted operators. It upgrades Laravel 10 → 12, requires PHP 8.2+, replaces the legacy admin UI with a React SPA, migrates `users.id` from integers to UUIDs, and enforces Twilio webhook signature validation on every inbound call. Read this guide completely before you deploy.

## 1. Back up your database

Take a full MySQL/MariaDB backup **before** you upload the new code or run any migration. `php artisan migrate:rollback` is not a supported recovery path for production:

- The UUID migration rewrites every row in `users` and changes the primary key type.
- If anything goes wrong mid-migration, restore from **your** backup — not from `migrate:rollback`.
- The migration creates a `users_pre_uuid_backup` table during `up()` as an emergency artifact, but you should not treat that as your upgrade rollback plan.

## 2. Migrations and the first HTTP request

Yap runs database migrations from the web middleware on incoming requests (see `DatabaseMigrations` middleware). Understand what happens **before** you point traffic at the new folder:

| Migration type | Behavior on first HTTP request |
|---|---|
| **Safe** (schema additions, indexes, new tables) | Applied automatically on the first request that reaches the new code. |
| **Destructive** (UUID conversion of `users.id`) | **Blocked.** Every web request returns HTTP 503 with a "Database Upgrade Required" page until you run `php artisan migrate` manually from a shell. |

**Take the site down or block traffic** until you have:

1. A verified database backup.
2. Run `php artisan yap:preflight` successfully (see below).
3. A maintenance window to run `php artisan migrate` when you are ready for the UUID conversion.

Do not let Twilio webhooks or operators hit the new folder until you are prepared. Even "safe" auto-migrations modify your database on the first request.

### Upgrade procedure (summary)

1. Create a new folder with the Yap 5.0 code.
2. Copy `config.php` from your 4.5.x install.
3. Run preflight (step 3 below).
4. Point your web server at the new folder **only after** preflight passes.
5. Run `cd src && php artisan migrate` to apply the UUID migration.
6. Confirm with `GET /api/v1/upgrade`.

See also [Upgrading](./upgrading.md) for the step-by-step checklist.

## 3. Run `php artisan yap:preflight` before deploying

From the **new** Yap 5.0 folder, with your existing `config.php` pointing at your production database:

```bash
cd src
php artisan yap:preflight
```

Preflight validates your environment and database **without serving web traffic**. It exits with a non-zero status when any blocking check fails and prints remediation guidance for each one.

| Check | Blocking? | What it means |
|---|---|---|
| **Required settings** | FAIL | A value from `minimalRequiredSettings()` is missing or empty in `config.php`. Set each required key before upgrading. |
| **Twilio auth token** | FAIL | `twilio_auth_token` is missing or empty. Every inbound Twilio webhook will return HTTP 403 in 5.0 (see section 4). |
| **Twilio signature bypass** | WARN | `TWILIO_DISABLE_SIGNATURE_VALIDATION` is enabled outside production. Do not use this on a live helpline. |
| **Trusted proxies** | WARN | `TRUSTED_PROXIES` is unset. Fine for direct connections; required behind a reverse proxy (see section 4). |
| **Session driver** | FAIL | `SESSION_DRIVER=database`. Conflicts with Yap's call-PIN `sessions` table (see section 6). |
| **APP_ENV** | WARN | Not exactly `production`. Several security guards only apply strict behavior when `APP_ENV=production`. |
| **PHP version** | FAIL / WARN | FAIL below PHP 8.2. WARN if below PHP 8.5 (official Docker image target). |
| **Database connection** | FAIL | Cannot connect with your `config.php` MySQL settings. |
| **MySQL version** | FAIL | Below MySQL 8.0 or MariaDB 10.3 (Laravel 12 requirement). |
| **Duplicate usernames** | FAIL | Two or more `users` rows share a username. The UUID migration maps by username; duplicates collide. |
| **Empty usernames** | FAIL | One or more `users` rows have NULL or empty `username`. |
| **Users table schema** | FAIL | `users.id` lacks a primary key, or `users.username` lacks a unique index. |

Fix every `[FAIL]` before continuing. After deploy, `GET /api/v1/upgrade` returns the same `checks` array plus root-server, Google Maps, and Twilio webhook validations.

## 4. `twilio_auth_token` is now required

Yap 4.5.x did **not** validate Twilio request signatures. Yap 5.0 validates `X-Twilio-Signature` on **every** Twilio-facing route — the full IVR, SMS gateway, voicemail, dialback, status callbacks, and experimental WebRTC/WebChat webhooks. All of these routes are inside the `twilio.signature` middleware group in `src/routes/web.php`.

`ValidateTwilioSignature` **fails closed**:

- An empty `twilio_auth_token` → HTTP **403** on every inbound call. Operators who never set a token had a working 4.5.x and a completely dead 5.0.0.
- A missing or invalid signature → HTTP **403**.
- A URL mismatch (proxy/host/scheme) → HTTP **403** (same total outage).

Set `twilio_auth_token` in `config.php` to the Auth Token for the Twilio account that owns your phone numbers. Run preflight to confirm it is present before you deploy.

### Reverse proxies and `TRUSTED_PROXIES`

Twilio signs the **public** URL it calls. Yap validates against `$request->fullUrl()`, which only honors `X-Forwarded-Host`, `X-Forwarded-Proto`, `X-Forwarded-Port`, and `X-Forwarded-Prefix` from **trusted** proxies (`TrustProxies` middleware).

| Deployment | `TRUSTED_PROXIES` |
|---|---|
| Direct Apache/nginx to PHP (no proxy) | leave unset |
| ngrok, Cloudflare, single-hop load balancer | `*` |
| Known proxy IPs | comma-separated list |

If Yap sits behind a reverse proxy but `TRUSTED_PROXIES` is unset, signature validation compares against the internal host/scheme (for example `http://localhost`) and **every call 403s**.

If your proxy strips a URL path prefix (public URL `https://example.org/yap/index.php`, app sees `/index.php`), the proxy must send `X-Forwarded-Prefix: /yap` and `TRUSTED_PROXIES` must be set.

### Per-service-body Twilio subaccounts

Service-body overrides for `twilio_auth_token` apply to signature validation **only on the first webhook of a call**, and only when `override_service_body_id` (or `service_body_id`) is present so `CallSession` can load the override before `call_state` is set. Mid-call webhooks keep the token established at call start.

Helplines that resolve the service body later in the IVR (without `override_service_body_id` on the initial webhook) must use the global `config.php` auth token for the Twilio account that owns the phone number.

### Development only

`TWILIO_DISABLE_SIGNATURE_VALIDATION=true` bypasses validation **only outside production**. Do not enable it on a live helpline.

## 5. `users.id` is now a UUID

The migration `2025_01_01_163927_convert_id_to_guid_in_users_table` replaces integer `users.id` values with UUIDs. Any external reporting script, BI export, or custom integration that joins on the integer id **breaks** after upgrade.

- Preflight checks for duplicate and empty usernames before the migration runs.
- Sanctum API tokens reference `tokenable_id` as a UUID after upgrade.
- If you store Yap user ids anywhere outside Yap, plan to re-map them or switch to username as the stable key.

## 6. Do not set `SESSION_DRIVER=database`

Laravel's `config/session.php` defaults to the `file` driver, which masks a naming collision:

- Laravel expects a `sessions` table for `SESSION_DRIVER=database`.
- Yap's `sessions` table stores **call PINs** (`callsid`, `timestamp`, `pin`) for dialback — not Laravel admin sessions.

If you set `SESSION_DRIVER=database`, Laravel will read and write the wrong table. Use `file` (default), `redis`, or another driver. Preflight fails if `SESSION_DRIVER=database`.

## 7. Removed and moved routes

Update bookmarks, monitoring probes, and automation that hit legacy URLs.

| 4.5.x route | 5.0.0 status |
|---|---|
| `/callWidget` | **Removed** |
| `/v1/session/delete` | **Removed** |
| `/admin/auth/rights` | **Removed** (admin auth is API-driven; see section 8) |
| `/admin/auth/logout` | **Removed** |
| `/admin/auth/timeout` | **Removed** |
| `/admin/auth/invalid` | **Removed** |
| `/adminv2{page}` | **Removed** — use `/admin{page}` (React SPA) |
| `DELETE /admin/cache` | **Removed** — use authenticated `POST /api/v1/cache` |
| `/upgrade-advisor` | **Moved** → `GET /api/v1/upgrade` |
| `/version` | **Moved** → `GET /api/v1/version` |

Twilio call-flow `.php` endpoints (`/index.php`, `/helpline-search.php`, etc.) are unchanged.

## 8. Auth is Sanctum now

The custom `AdminAuthenticator` and session-cookie admin API from 4.5.x are gone. The admin React SPA and any scripted admin access must authenticate through the REST API:

1. `POST /api/v1/login` with JSON `{"username":"...","password":"..."}`.
2. Use the returned bearer token on subsequent requests: `Authorization: Bearer <token>`.
3. Protected routes are under `/api/v1/*` with `auth:sanctum` middleware.

BMLT-based and database-local admin accounts both flow through this endpoint. Session cookies still back the browser UI, but API clients must use Sanctum tokens.

## 9. PHP and Laravel requirements

| Component | Requirement |
|---|---|
| **PHP** | `^8.2` per `composer.json`; **PHP 8.5** in the official `docker/Dockerfile` image |
| **Laravel** | 12.x |
| **MySQL** | 8.0+ (or MariaDB 10.3+) |

PHP 8.1 is no longer supported. If you run the official Docker image, you get PHP 8.5 on Apache. Bare-metal and shared-hosting installs must provide PHP 8.2+ with all [required PHP extensions](https://yap.bmlt.app/general/php-requirements) enabled — especially `pdo_mysql` and `fileinfo` (a missing `fileinfo` extension causes `Class 'finfo' not found`).

## 10. The admin UI is a React SPA

Yap 5.0 serves the admin portal as a single-page application at `/admin` (and sub-paths). The legacy server-rendered admin pages are gone.

**Reverse-proxy path rewriting:** The SPA loads assets from `/public/js/...` relative to your web root. If Yap is mounted under a sub-path, your proxy must forward the full path consistently and set `X-Forwarded-Prefix` so API calls and asset URLs resolve correctly.

**Theming:** Dark mode and Material UI theming are built into the React app (`resources/js/theme/`). Custom CSS injection from 4.5.x admin pages is not carried forward — revisit any operator-specific styling.

**Content-Security-Policy:** If your reverse proxy or web server injects a strict CSP, ensure it allows the compiled JS bundle, inline bootstrapping in `admin.blade.php`, and API calls to your own origin. A CSP that blocks inline scripts or `eval` may prevent the admin UI from loading.

## 11. WebChat and WebRTC are experimental and default off

`webchat_enabled` and `webrtc_enabled` default to `false` in 5.0.0. While disabled:

- Their routes are **not registered** — every WebChat/WebRTC endpoint returns **404**.
- Do not enable them on a production helpline in 5.0.0; they ship without test coverage and behavior may change.

If you toggle either setting and have run `php artisan route:cache`, run `php artisan route:clear` for the change to take effect.

## 12. Custom extensions: volunteer data shape change

In 4.5.x, `ConfigData::getVolunteers()`, `getVolunteersRecursively()`, and `getGroupVolunteers()` returned rows whose `data` field was a **raw JSON string** with base64-encoded shift schedules inside.

In 5.0.0, these methods return **decoded** objects:

- `data` is a parsed PHP array/object, not a JSON string.
- Each volunteer's `volunteer_shift_schedule` is base64-decoded and expanded to an array of shift objects (with `day_name` populated).

Custom extensions or external scripts that read volunteer config directly from the database or these APIs must expect decoded objects, not raw JSON strings.

## 13. Known regressions fixed in 5.0.0

If you ran **`5.0.0-beta1`** or **`5.0.0-beta2`**, upgrade to the final 5.0.0 release. Those betas shipped with regressions that are fixed in 5.0.0:

| Issue | Symptom | Fixed in |
|---|---|---|
| **Gender routing** [#1578] | On service bodies with `gender_routing_enabled`, a caller who pressed 2 (woman) or 3 (either) was routed to a **male** volunteer due to a `session()` rewrite bug. Callers often fell through to fallback or voicemail. | 5.0.0 |
| **Service-body override settings at login** [#1579] | Database-authenticated service-body admins saw global defaults in the settings UI because override config never seeded into the admin session. | 5.0.0 |
| **WebChat SMS when disabled** [#1577] | `/webchat-sms` accepted inbound messages even when WebChat was disabled. | 5.0.0 |

Beta releases did not document these breaking changes. See [RELEASENOTES.md](https://github.com/bmlt-enabled/yap/blob/main/RELEASENOTES.md) for the full 5.0.0 changelog.

## After upgrading

```bash
curl https://your-yap-host/api/v1/upgrade
```

Confirm all preflight checks pass, Twilio webhooks validate, and the admin UI loads. Place a test call through your full IVR path (including gender routing, if enabled) before reopening traffic.
