# Helpline Call Routing

---

The helpline router utilizes a BMLT server (2.9.0 or later), that has helpline numbers properly configured in the "Service Body Administration" section.

A prompt will ask for a piece of location information in turn it will look up latitude and longitude and then send that information to the BMLT root server you have configured.

You can also tie this into an existing extension based system, say for example Grasshopper.  If you want to dial an extension just add something like `555-555-5555|wwww700` for example after the helpline field on the BMLT Service Body Administration.  In this case it's instructing to dial 555-555-5555 and wait 4 seconds and then dial 700.

## Call routing filter

The `call_routing_filter` setting (Settings page or `config.php`) appends extra query parameters to the BMLT **GetSearchResults** helpline search URL. Use it when your root server needs additional filters to decide which service bodies are in coverage—for example format or custom-field constraints supported by your BMLT build.

Example in `config.php`:

```php
static $call_routing_filter = '&some_bmlt_parameter=value';
```

The value must be a URL query fragment (typically starting with `&`) that BMLT understands. Yap passes it to `helplineSearch` together with latitude, longitude, and [helpline search radius](./helpline-search-radius). Wrong values can return no coverage; test with a known location after changing the filter.

Service-body overrides follow [configuration precedence](../general/configuration-precedence).
