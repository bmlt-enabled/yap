---
layout: default
title: Blocklist
parent: General
nav_order: 11
---

# Blocklist

---


If you want to completely block a specific number you can use the setting as follows (comma-separated).

Sometimes it's best to look at the Caller querystring value in your logs or the Twilio console to see the exact number being passed.

```php
static $blocklist = "";
```
