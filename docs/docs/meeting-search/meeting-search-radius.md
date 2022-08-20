---
title: Meeting Search Radius
sidebar_position: 7
---

---

Change the default meeting search radius, this can be in miles or a negative number which would set the radius at the first n results. You can change this in your `config.php` with the following:

```php
static $meeting_search_radius = 30;
```
This would set the radius to a maximum of 30 miles.

```php
static $meeting_search_radius = -50;
```
This would set the radius at the first 50 results and is the default.

More information on how the BMLT uses search radius is here: [https://bmlt.app/how-auto-radius-works/](https://bmlt.app/how-auto-radius-works/)
