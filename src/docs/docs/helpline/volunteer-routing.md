# Volunteer Routing

---

Incompatible with Yap 1.x Volunteer Dialers — you will have to reconfigure your setup.

## Initial setup

1) You will need to ensure that the following `config.php` parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.

```php
static $bmlt_username = "";
static $bmlt_password = "";
```

2) You will need to specify Twilio API parameters.  You can find this on your account dashboard when you login into Twilio.

```php
static $twilio_account_sid = "";
static $twilio_auth_token = "";
```

3) Head over to your admin login page at `https://your-yap-instance/admin`.
4) Login with any credentials from your BMLT server (or a Yap-local user).
5) Go to the **Service Bodies** tab and click **Configure**.  Enable **Volunteer Routing** and save.
6) Go to **Volunteers**, select your service body, and add volunteers with phone numbers and shift windows.
7) Click **Enable** on each volunteer card, then **Save Volunteers**.
8) Drag and drop volunteer cards to set dial order.
9) Use **Include Group** to add a [volunteer group](./volunteer-groups) card instead of duplicating members.
10) Go to **Schedules** to preview the calendar for your service body.
11) Place a test call to confirm routing.

## Call strategies

Configure call strategies under **Service Bodies → Configure → Call Handling**. See [Call strategies](./call-strategies) for full detail on blasting, cycling, timeouts, and voicemail fallback.

| Strategy (summary) | Behavior |
|---|---|
| **Linear cycle** | Dial in order; linear strategies loop or stop at voicemail per setting. |
| **Blasting** | Ring all active volunteers; first answer wins; may fall through to voicemail. |
| **Random** | Random order each attempt or one shuffled pass, per setting. |

[Volunteer groups](./volunteer-groups) expand to their on-shift members when a GROUP card is enabled on the list.

## Volunteer responder

**Responder** is a per-volunteer checkbox (not a call strategy). It marks who receives voicemail SMS links and similar notifications. See [Volunteer responder](./volunteer-responder).

## Redirect and overrides

* **Volunteer Routing Redirect**: Set the mechanism to "Volunteers Redirect" and specify the target service body ID in **Volunteers Redirect Id**.
* **Forced Caller Id**: Changes the outgoing display caller ID for volunteer outdials.
* **Call Timeout**: Seconds before trying the next number (or next strategy step).

See also [checking call routing](./checking-call-routing), [helpline call routing](./helpline-call-routing), and the video [Call Blasting and other Call Strategies Explained](../videos/call-blasting-and-other-call-strategies-explained).
