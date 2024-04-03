# Setup

---

**This will require that you have an SSL certificate installed on your webserver to transit a secure connection.  This is required by Twilio.**

1. Create a new virtual application or add the yap code to an existing folder.  You can always find the latest stable version here (be sure download the yap-x-x-x.zip file and not the source code): [https://github.com/bmlt-enabled/yap/releases/latest](https://github.com/bmlt-enabled/yap/releases/latest).  You can also try out or help test the latest bleeding edge features by installing one of the [unreleased versions](https://archives.bmlt.app/index.html#yap/).  The newest version would always be the highest numbered build.  

2. Once the application is configured you will need to customize the config.php file.  There are several settings there that are documented in that file.  There are a number of different ways to utilize the yap platform. 

3. You will need to (`config.php`) enter `$twilio_account_sid` and `$twilio_auth_token`.  You can find this on your account dashboard.  You can also use a different Twilio account using the admin portal under "Service Bodies".  Keep in mind that if a key or keys are set at any parent above, all child service bodies will inherit that key.  In order to use a key, just specify `override_service_body_id` in your webhook with the applicable id.  You will also need to set a webhook for Call Detail Records.

![twilio-status-callback](/img/status_callback_example.png)

4. You will need to ensure that the following `config.php` parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.  The user should be at the highest level of access in your BMLT hierarchy that you require access to. 
```php
static $bmlt_username = "";
static $bmlt_password = "";
```

5. Be sure to get a Google Maps API key.  Specify this in config.php as the value for `$google_maps_api_key`.  Make sure you have "Google Maps Geocoding API" and "Google Maps Time Zone API" enabled on your credentials.  This key must be separate from your BMLT key with no server restrictions, this is safe because yap never passes the key client side.  You can login into your Google API console here: [https://console.cloud.google.com/apis/](https://console.cloud.google.com/apis/).  This article may be useful [https://bmlt.app/google-maps-api-keys-and-geolocation-issues/](https://bmlt.app/google-maps-api-keys-and-geolocation-issues/).

6. Try testing that your application actually is functioning properly by opening a browser http://example.com/index.php.  

7. You will need to set up a Twilio account, and do the following.  Purchase a phone number (typically you would buy one for your locale, tollfree is pretty much unnecessary these days).  Configure that number to point to a Webook.  It would be something like https://example.com/index.php.
    
8. You will need to set up a new MySQL database.  Be sure to set up backups on your database as well.  Your hosting provider may cover this more.
Once you've done that, set the following in your config.php.
```php
static $mysql_hostname = "";
static $mysql_username = "";
static $mysql_password = "";
static $mysql_database = "";
```

9. You can test whether or not you are properly configured by going to https://example.com/upgrade-advisor.php.  This will also run MySQL scripts to initialize/update your database.

10. Make a call to your number and try it out.  If there is a problem the debugger in the Twilio console will let you know why.  Most likely you did not setup your config.php file correctly.
