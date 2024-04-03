# Custom Geocoding

---

You can override the geocoding response by pre-populating exact address and postal code lookups with a Latitude and Longitude.  If specifying this at the server level use the following syntax for example:

```php
static $custom_geocoding = [['location' => "Pasco County, FL", 'latitude' => "0.00", 'longitude' => "0.00"]]
```

If you are using the Config option in the Admin UI to override by service body, use JSON formatting instead as seen below.

```php
[{"location": "Pasco County, FL", "latitude": "0.00", "longitude": "0.00"}]
```
