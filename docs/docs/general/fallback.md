---
layout: default
title: Fallback
parent: General
nav_order: 9
---

# Fallback

---


There may be times when a root server is down, it's possible to redirect a call to another if this happens.  In your `config.php`, specify the following.

```php
static $helpline_fallback = "1919555555";
```
