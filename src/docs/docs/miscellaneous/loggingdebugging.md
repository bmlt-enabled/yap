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
- **HTTP 503 Database Upgrade Required** — a migration that requires operator attention is pending after a major upgrade. Contact your server administrator or hosting provider. See [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x).
- **Silent hang-ups** — check `laravel.log` and Twilio's request inspector for the call SID.

## Upgrade advisor

`GET /api/v1/upgrade` validates configuration, database shape, Twilio webhooks, Google Maps connectivity, and Twilio compliance settings. Use it after any config change.

Open the URL in a browser, or log in to the admin portal and review **Dashboard** or **System Health**. No shell access is required.

## Webhook failures

When one of yap's Twilio webhooks returns an HTTP 5xx, Twilio plays "an application error has occurred" to the caller. Yap records each of these and shows them to top-level admins under **System Health → Twilio Webhook Failures**, with a count on the **Dashboard**:

* how many failed in the last 24 hours and the last 7 days;
* for each recent failure: the time, the webhook, the HTTP status, the CallSid (or SMS sid), the exception and the file and line it came from;
* the events yap recorded for that call, so you can see how far the caller got.

Failures are kept for 30 days. They hold the CallSid, which call records already store, but not the caller's number or anything they said, typed or texted: those are removed from the exception message before it is saved, and the query string is never stored. The same data is available as JSON from `GET /api/v1/webhookFailures` (top-level admins only).

Twilio's own error log (**Monitor → Logs → Errors** in the Twilio Console) still catches what yap never sees, such as timeouts and invalid TwiML.

## Call-flow tracing

Set `$debug = true` and place a test call. Yap logs the Twilio request parameters and routing decisions. Pair this with Twilio's call SID in both logs to follow a single caller through the IVR.
