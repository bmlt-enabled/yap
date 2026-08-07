---
title: Upgrading from Yap 4.x to Yap 5.x
sidebar_position: 6
---

Yap 5.0 validates [Twilio request signatures](https://www.twilio.com/docs/usage/security#validating-requests) on **every** inbound webhook — the full IVR, SMS gateway, voicemail, dialback, status callbacks, and experimental WebRTC/WebChat routes. This is a breaking change from 4.5.x, which did not validate signatures at all.

## Before you upgrade

1. **Set `twilio_auth_token` in `config.php`.** The middleware fails closed: if the auth token is missing or empty, every inbound Twilio request returns HTTP 403 and callers hear silence. Operators who never configured `twilio_auth_token` had a working 4.5.x and would have a completely dead 5.0.0.

2. **Run the preflight check** from your Yap directory:

   ```bash
   php artisan yap:preflight
   ```

   Also review `/api/v1/upgrade` in the admin UI — it now treats an empty `twilio_auth_token` as a hard failure.

3. **Configure trusted proxies if you use a reverse proxy, load balancer, or tunnel.** Twilio signs the public URL it calls. Yap validates against `$request->fullUrl()`, which only honors `X-Forwarded-Host`, `X-Forwarded-Proto`, `X-Forwarded-Port`, and `X-Forwarded-Prefix` from **trusted** proxies.

   | Deployment | `TRUSTED_PROXIES` |
   |---|---|
   | Direct Apache/nginx to PHP (no proxy) | leave unset |
   | ngrok, Cloudflare, single-hop LB | `*` |
   | Known proxy IPs | comma-separated list |

   If `TRUSTED_PROXIES` is unset but your proxy sends `X-Forwarded-Host` / `X-Forwarded-Proto`, signature validation compares against `http://localhost` (or your internal host) and **every call 403s** — the same total outage as a missing auth token.

   If your proxy strips a URL path prefix (public URL `https://example.org/yap/index.php`, app sees `/index.php`), the proxy must send `X-Forwarded-Prefix: /yap` and `TRUSTED_PROXIES` must be set. Without the prefix header, signatures will not match.

## Per-service-body Twilio subaccounts

Service-body overrides for `twilio_auth_token` apply to signature validation **only on the first webhook of a call**, and only when `override_service_body_id` (or `service_body_id`) is present in that request so `CallSession` can load the override before `call_state` is set. Mid-call webhooks keep the token established at call start; service-body credential overrides are not re-applied once `call_state` exists.

Helplines that resolve the service body later in the IVR (without `override_service_body_id` on the initial webhook) must use the global `config.php` auth token for the Twilio account that owns the phone number.

## Development and testing

`TWILIO_DISABLE_SIGNATURE_VALIDATION=true` bypasses validation **only outside production**. The test suite sets this in `phpunit.xml` by default. Do not enable it on a live helpline.

## General upgrade steps

Make a new folder with the newer version and copy over `config.php`. Once you feel comfortable you can delete the older folder and rename it.

Be sure to run `/api/v1/upgrade` after deploying.
