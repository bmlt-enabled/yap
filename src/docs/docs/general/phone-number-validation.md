# Phone Number Validation

---

By default, phone number validation on volunteer entry is set to the US.  You change this by setting the setting `static $phone_number_validation = "US"` to a the correct two letter country code.  The codes are found [here](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2). 

You currently cannot mix countries in a single service body.  It is assumed that your callers are in the same country.  If you need to do this for more than one country, then you would need to disable this setting by setting the value to `""`.
