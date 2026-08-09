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

Yap writes Laravel logs to `src/storage/logs/laravel.log`. Check this file when the admin SPA or API returns 500 errors without detail in the browser. On shared hosting you may need to use your provider's file manager or log viewer to read this path.

## Twilio debugger

The Twilio Console **Monitor → Logs → Errors** page shows webhook failures (403 signature errors, 500 PHP exceptions, timeouts). Each failed request includes the response body Yap returned.

Common Twilio issues:

- **HTTP 403 on every call** — empty `twilio_auth_token`, or `TRUSTED_PROXIES` unset behind a reverse proxy.
- **HTTP 503 Database Upgrade Required** — a destructive database migration is pending after a major upgrade. Contact your server administrator or hosting provider to apply it during a maintenance window. See [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x).
- **Silent hang-ups** — check `laravel.log` and Twilio's request inspector for the call SID.

## Upgrade advisor

`GET /api/v1/upgrade` validates configuration, database shape, Twilio webhooks, Google Maps connectivity, and Twilio compliance settings. Use it after any config change.

Open the URL in a browser, or log in to the admin portal and review **Dashboard** or **System Health**. No shell access is required.

## Call-flow tracing

Set `$debug = true` and place a test call. Yap logs the Twilio request parameters and routing decisions. Pair this with Twilio's call SID in both logs to follow a single caller through the IVR.
