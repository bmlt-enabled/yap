---
title: WhatsApp Helpline
sidebar_position: 21
---

# WhatsApp Helpline

Travelers on hotel or airport Wi-Fi often cannot dial a U.S. number from the native phone app. Yap can serve those people over **WhatsApp** using the same Twilio account and the same SMS gateway you already run.

There is no separate Yap product to install. A WhatsApp-enabled Twilio sender posts to `sms-gateway.php` the same way SMS does. Yap keeps the conversation on WhatsApp for the seeker, and notifies on-shift SMS volunteers as usual.

## What seekers can do

- Send a **city, county, or zip code** to get meeting results back in WhatsApp.
- Send the helpline keyword (default `talk`) plus a location — for example `talk Orlando` — to request a volunteer.

Volunteers are told the request arrived on WhatsApp and get a `https://wa.me/` link so they can message the seeker on WhatsApp (which also works over Wi-Fi).

## Twilio setup

1. In Twilio, enable WhatsApp on a sender ([Twilio WhatsApp](https://www.twilio.com/docs/whatsapp)). Production senders go through Meta Business verification; the Twilio sandbox is enough to try the flow.
2. Point that sender's **incoming messaging webhook** at the same URL you use for SMS: `https://your-yap-host/sms-gateway.php`.
3. Keep your existing voice webhook on `index.php`. WhatsApp messaging does not replace the phone helpline.

Twilio inbound webhooks use addresses like `whatsapp:+14075551212`. Yap detects that prefix and replies on WhatsApp. You do not need a feature flag for inbound handling.

## Public landing page

Set the number you want printed on flyers and hotel cards:

```php
static $whatsapp_number = '+14075551212';
```

Then open `https://your-yap-host/whatsapp`. That page is a large tap target that launches WhatsApp (`wa.me`) and explains the two message formats. Print it for concierge desks, airports, and meeting lists.

Optional prefilled first message when someone taps **Open WhatsApp**:

```php
static $whatsapp_prefill = 'talk Orlando';
```

## Volunteer SMS caller ID

Seekers stay on WhatsApp. Volunteers are still notified by ordinary SMS. Twilio will not send an SMS to a volunteer from a `whatsapp:` address, so Yap strips the prefix (the same Twilio number is usually SMS-capable) or uses an override:

```php
static $whatsapp_sms_from = '+14075551212';
```

If the service body has a forced caller ID, that number is used when `$whatsapp_sms_from` is empty.

## Other Wi-Fi options

WhatsApp is the production path for people who already have the app. Yap also ships:

- **[Experimental WebRTC / WebChat widgets](/miscellaneous/experimental-web-widgets)** — in-browser calling and chat, disabled by default.
- **[Connectors](/miscellaneous/connectors)** — Facebook Messenger bot and Alexa skill for meeting search.

## Related topics

- [SMS Gateway](/meeting-search/sms-gateway) — the webhook WhatsApp shares with SMS
- [SMS Volunteer Routing](/helpline/sms-volunteer-routing) — `talk` keyword and volunteer shifts
