---
title: Grace Period
sidebar_position: 5
---

---

This is so that yap still returns results for meetings that have already started.  By default a 15 minute grace period will be applied.  This can be adjusted by setting `$grace_minutes` in your `config.php`.

```php
static $grace_minutes = 10;
```

This would set the grace period to ten (10) minutes.
