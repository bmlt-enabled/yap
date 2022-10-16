# SMS Options

---
## Summary Page

You can configure the SMS results as a summary page link.

Set your configuration to have

```php
static $sms_summary_page = true;
```

## Adding Map Links

Some older handsets are not capable of rendering maps links.  If you want to enable this feature add the following to your `config.php` file.

```php
static $include_map_link = true;
```

## Adding Location Text

This feature enables you to include the name of the meeting location in search results, by default it is disabled.  If you want to enable this feature add the following to your `config.php` file.

```php
static $include_location_text = true;
```

## Adding Distance Details

This feature allow you to include the distance from the search criteria in an SMS response. You can specify either mi or km.

```php
static $include_distance_details = 'mi';
```

## Combine Results

Combining to a single SMS versus individual ones.

Set your configuration to have

```php
static $sms_combine = true;
```

## Blackhole

This feature will not return an SMS to any numbers specified in a comma separated list.

```php
static $sms_blackhole = '+12125551212';
```
