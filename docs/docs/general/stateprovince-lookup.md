# State/Province Lookup
---

It may be that your instance needs to search multiple states. By default searches will be biased towards the local number state (unless it's tollfree).  To enable province lookup set the `$province_lookup`, variable to `true` in the `config.php` file.  

```php
static $province_lookup = true;
```

You can also specify a predetermined list of provinces / states. If you use this setting, then a speech gathering will be replaced with a numbered menu of states. Currently it would support up to 9 states in the list. To enable this do the following for example (the order in the list and position is the number that will be said to be pressed in the menu):

```php
static $province_lookup = true;
static $province_lookup_list = ["North Carolina", "South Carolina"];
```
