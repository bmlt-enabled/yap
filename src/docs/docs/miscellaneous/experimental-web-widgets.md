---
title: Experimental Web Widgets
sidebar_position: 9
---

# Experimental Web Widgets (WebChat and WebRTC)

WebChat and WebRTC browser widgets are **experimental** and ship **disabled by default**. Do not enable them on a production helpline without understanding the risks and limitations.

## WebRTC dial widget

When `webrtc_enabled` is true in `config.php`, Yap exposes token and config endpoints under `/api/v1/webrtc/`. Embed the widget loader from your Yap host to let visitors start a browser-based call.

Required Twilio settings (API key, secret, TwiML app SID) must be configured. Rate limits apply (`webrtc_token_rate_limit`, `webrtc_call_rate_limit`).

## WebChat widget

When `webchat_enabled` is true, Yap exposes session and messaging endpoints under `/api/v1/webchat/`. Operators can receive SMS notifications when a visitor requests chat coverage.

Related settings include `webchat_timeout_minutes`, `webchat_meeting_search_enabled`, and customizable no-coverage / no-volunteer messages.

## Operational notes

- Both features require Twilio credentials with appropriate permissions.
- Widget endpoints are rate-limited and return 404 when the feature flag is off.
- Test in a staging environment before enabling on a live regional helpline.
- For standard telephone helpline operation, leave both flags at their default (`false`).
