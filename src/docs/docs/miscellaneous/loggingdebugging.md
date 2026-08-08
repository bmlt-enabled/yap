---
title: Logging/Debugging
sidebar_position: 3
---

# Logging and Debugging

## Application debug mode

To enable verbose PHP error output during troubleshooting, set in your `config.php`:

```php
static $debug = true;
```

Turn this off on production helplines once you have captured the issue.

## Laravel logs

Yap writes Laravel logs to `src/storage/logs/laravel.log`. Check this file when the admin SPA or API returns 500 errors without detail in the browser.

## Twilio debugger

The Twilio Console **Monitor → Logs → Errors** page shows webhook failures (403 signature errors, 500 PHP exceptions, timeouts). Each failed request includes the response body Yap returned.

Common Twilio issues:

- **HTTP 403 on every call** — empty `twilio_auth_token`, or `TRUSTED_PROXIES` unset behind a reverse proxy.
- **HTTP 503 Database Upgrade Required** — run `php artisan migrate` after a major upgrade.
- **Silent hang-ups** — check `laravel.log` and Twilio's request inspector for the call SID.

## Upgrade advisor

`GET /api/v1/upgrade` (or `php artisan yap:preflight` before deploy) validates configuration, database shape, Twilio webhooks, and Google Maps connectivity. Use it after any config change.

## Call-flow tracing

Set `$debug = true` and place a test call. Yap logs the Twilio request parameters and routing decisions. Pair this with Twilio's call SID in both logs to follow a single caller through the IVR.
