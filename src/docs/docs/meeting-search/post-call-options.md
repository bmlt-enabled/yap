# Post Call Options

---

## Making SMS results for voice calls optional

The default of the system is to send an SMS after each voice meeting result.  As an option to you audience you can add the following parameter to your `config.php` file.

```php
static $sms_ask = true;
```

By setting this, a prompt will be played at the end of the results, asking if they would like the results texted to them.  If they do not respond the call will automatically hang up in 10 seconds.


## Infinite Searches

You can provide an option to allow someone to search again.  Just set:

```php
static $infinite_searching = true;
``` 

## Suppress Voice Results
In order to prevent voice results from being returned for meeting searches set the following setting:

```php
static $suppress_voice_results = true;
```

## Disable Meeting Results SMS
In order to prevent meeting results being returned as an SMS on a voice call use this setting:

```php
static $sms_disable = true;
```
