---
title: Post Call Options
sidebar_position: 9
---

---

## Making SMS results for voice calls optional

The default of the system is to send an SMS after each voice meeting result.  As an option to you audience you can add the following parameter to your `config.php` file.

```php
static $sms_ask = true;
```

By setting this, a prompt will be played at the end of the results, asking if they would like the results texted to them.  If they do not respond the call will automatically hang up in 10 seconds.


## Infinite Searches

You can provide an option to allow someone to search again.  Just set:

```php
static $infinite_searching = true;
``` 
