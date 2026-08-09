---
title: Experimental Web Widgets
sidebar_position: 9
---

# Experimental Web Widgets (WebChat and WebRTC)

WebChat and WebRTC browser widgets are **experimental** and ship **disabled by default**. Do not enable them on a production helpline without understanding the risks and limitations.

## Demo page

Yap ships a local demo at `/widget-demo.html` on your Yap host (for example `https://your-yap-instance/widget-demo.html`). It shows combined, call-only, and chat-only embeds and checks whether `webrtc_enabled` and `webchat_enabled` are active.

Use the demo to validate Twilio API keys, TwiML app SID, and SMS webhook configuration before embedding on a public site.

## Widget loader (`widget-loader.js`)

External sites embed widgets via `widget-loader.js` served from your Yap installation:

```html
<!-- Combined widget (call + chat tabs) -->
<div id="yap-widget" data-yap-widget data-yap-api-url="https://your-yap-server.com"></div>
<script src="https://your-yap-server.com/widget-loader.js"></script>
```

Other patterns supported by the loader:

```html
<!-- Call-only -->
<div data-yap-widget="call" data-yap-api-url="https://your-yap-server.com"></div>

<!-- Chat-only -->
<div data-yap-widget="chat" data-yap-api-url="https://your-yap-server.com"></div>
```

Programmatic initialization:

```html
<div id="my-widget"></div>
<script src="https://your-yap-server.com/widget-loader.js"></script>
<script>
  YapWidget.load({
    container: '#my-widget',
    apiUrl: 'https://your-yap-server.com',
    type: 'combined', // 'call', 'chat', or 'combined'
    serviceBodyId: '123',
    title: 'NA Helpline'
  });
</script>
```

The loader fetches `/js/dial-widget.js` from the same host and queues initialization until the script loads.

## WebRTC dial widget

When `webrtc_enabled` is `true` in `config.php`, Yap registers:

- Token and config endpoints under `/api/v1/webrtc/`
- Twilio call-flow routes `/webrtc-call` and `/webrtc-status`

Required Twilio settings in `config.php`:

- `twilio_api_key`
- `twilio_api_secret`
- `twilio_twiml_app_sid`

Rate limits: `webrtc_token_rate_limit`, `webrtc_call_rate_limit`. Restrict browser origins with `webrtc_allowed_origins` (default `*`).

## WebChat widget

When `webchat_enabled` is `true`, Yap registers:

- Session and messaging endpoints under `/api/v1/webchat/`
- SMS bridge webhook `/webchat-sms` (configure on your Twilio number for volunteer replies)

Typical `config.php` flags:

```php
$webchat_enabled = true;
$webchat_timeout_minutes = 30;
$webchat_meeting_search_enabled = true;
$webchat_no_coverage_message = 'Sorry, there is no coverage for your location.';
$webchat_no_volunteers_message = 'Sorry, no volunteers are available at this time.';
$webchat_volunteer_sms_prefix = 'New web chat request';
$webchat_rate_limit = 10;
```

Run `php artisan migrate` so the `chat_sessions` table exists before enabling WebChat.

WebChat notifies on-shift volunteers via SMS (blasting). The first volunteer to reply claims the session; messages bridge between the browser and SMS.

## Operational notes

- Both features require Twilio credentials with appropriate permissions.
- While disabled, routes are **not registered**—widget and API endpoints return **404**.
- Clear Laravel route cache after toggling flags if your host uses `php artisan route:cache`.
- Test in staging with `widget-demo.html` before embedding on a live regional helpline.
- For standard telephone helpline operation, leave both flags at their default (`false`).

## Related topics

- [Sanctum API access](./sanctum-api-access) — admin API authentication (not used by public widgets)
- [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) — section 11 on experimental flags
