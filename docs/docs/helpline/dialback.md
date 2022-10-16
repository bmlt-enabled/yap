---
title: Dialback
sidebar_position: 16
---

---

You can dialback a caller as the helpline number. If the caller leaves a voicemail there is a PIN that is stored for each call and visible in the admin portal under the voicemail section.

You would call your helpline number and push `9` from there you will be prompted for a unique PIN that will connect you with the caller.

When `sms_dialback_enabled` is enabled some SMS messages will have information about the PIN and a special magic link for dialing back. 
These SMS messages are 
1) when a volunteer receives an incoming call SMS and
2) when a voicemail notification SMS is received.

```php
static $sms_dialback_enabled = true;
```
