# Exclude Errors

---

There may be a desire to hide some errors on the admin UI login page.  You can use the following option:

```php
static $exclude_errors_on_login_page = ["twilioMissingCredentials"];
```

If you set fake credentials, for example for a large server that you do not intend the config.php to have real creds and you will only use service body overrides, you can suppress the rest errors from the upgrade advisor.

```php
static $exclude_errors_on_login_page = ["twilioFakeCredentials"];
```
