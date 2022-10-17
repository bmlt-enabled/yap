# Exclude Errors

---

There may be a desire to hide some errors on the admin UI login page.  You can use the following option:

```php
static $exclude_errors_on_login_page = ["twilioMissingCredentials"];
```

Right now this is the only option available.  There may be others in the future.
