# Location Lookup Bias

---

By default location lookups are biased toward the US.  You can create a series of refinements by using the `$location_lookup_bias` in config.php.

For example say you wanted to lookup Bayonne.  By default Bayonne, New Jersey would be interpreted.  If you were intended for France you would set your config as the following:

```php
static $location_lookup_bias = "country:France";
```

A full listing of available bias options are available here: [https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering](https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering).  You can use as few or as many as you want, by separating each set with pipe "\|" character.
