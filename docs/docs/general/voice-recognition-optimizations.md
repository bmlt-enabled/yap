---
layout: default
title: Voice Recognition Optimizations
parent: General
nav_order: 2
---

# Voice Recognition Optimizations

---

It's possible to set the expected spoken language, for recognition by setting the following variable in config.php to the culture variant.  The default is `en-US`, which is US English.

Use the this chart to find the code of your preference [https://www.twilio.com/docs/api/twiml/gather#languagetags](https://www.twilio.com/docs/api/twiml/gather#languagetags).

```php
static $gather_language = "en-US";
```

You can also set some expected words or hints, to help the voice recognition engine along.  Use the setting by separating words with commas.  You can use phrases as well.  

Each hint may not be more than 100 characters (including spaces).  You can use up to 500 hints.

```php
static $gather_hints = "";
```

**New Yap 3.x** Voice recognition for input gathering is turned on by default, to turn it off you can do the following.

```php
static $speech_gathering = false;
```
 