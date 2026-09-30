# SMS Gateway
---

In order to use SMS to get a list of meetings you will configure Messaging to point to Webhook sms-gateway.php.

Then you can send a zip code, county or city to your phone number and get back a response.

The same webhook also accepts [WhatsApp](/helpline/whatsapp) when the Twilio sender is WhatsApp-enabled. Inbound addresses look like `whatsapp:+14075551212`; Yap replies on that channel automatically.

![yap-twilio-sms-hook-image](/img/yap-twilio-sms-hook.png)
