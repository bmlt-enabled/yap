---
layout: default
title: Voicemail
nav_order: 11
parent: Helpline / Volunteer Routing
---

## Voicemail

---


This is configured through service body call handling, through your call strategy setting.  If you specify a Primary Contact Number, it will SMS a link to that person when a voicemail is left.

You can also comma separate the values if you want it to go to more than one person.

Voicemail links are also available in the Twilio Console under "Recordings".  

You can also optionally use email.  You will have to enable this by adding an email address under the Primary Contact Email.  You can optionally supply a list of comma separated emails for multiple recipients.

You will also need to ensure that the following settings are in your `config.php`.

```php
static $smtp_host = '';             // the smtp server
static $smtp_username = '';         // the smtp username
static $smtp_password = '';         // the smtp password
static $smtp_secure = '';           // either ssl (port 486) or more securely tls (port 587)
static $smtp_from_address = '';     // the address where the email will be sent from
static $smtp_from_name = '';        // the label name on the from address
```

If you need to, for some reason, to override the port here is another optional setting.

```php
static $smtp_alt_port = '';         // enter the integer for the respective to use
```

If you do not receive an email, check your server logs.  There should be some good information there.  Also the upgrade advisor should give you some information about what might be missing as long as $smtp_host is set.
