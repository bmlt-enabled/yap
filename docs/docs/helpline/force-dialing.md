---
title: Force Dialing
sidebar_position: 4
---

---

You might want to force a particular Twilio number to just call another number.  Just use the following webhook.

`/helpline-search.php?ForceNumber=8885551212` or for extension dialing `/helpline-search.php?ForceNumber=8885551212%7Cwwww700`.  Each `w` is a 1 second pause.

In some cases, when using 1 second pauses you may want to indicate that there is something happening to the end user as there will be a delay.

If you would like there to be a CAPTCHA to prevent robocalls + fax machines, you can add this to your query.

`&Captcha=1`

And/or, if you would like to have a basic waiting message, but no CAPTCHA use.

`&WaitingMessage=1`

These options can be combined.
