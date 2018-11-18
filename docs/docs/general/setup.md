---
layout: default
title: Setup
parent: General
nav_order: 1
---

# Setup

---

Here is an [instructional video](https://www.dropbox.com/s/e59dzal4rkkcl2r/twilio.mp4?dl=0) that might assist you.

**This will require that you have an SSL certificate installed on your webserver to transit a secure connection.  This is required by Twilio.**

1. Create a new virtual application or add the yap code to an existing folder.  The easiest way to get the code there is to upload the latest version there: [https://github.com/bmlt-enabled/yap/archive/2.5.1.zip](https://github.com/bmlt-enabled/yap/archive/2.5.1.zip).

2. Once the application is configured you will need to customize the config.php file.  There are several settings there that are documented in that file.  There are a number of different ways to utilize the yap platform. 

3. **NEW**>> You will need to (`config.php`) enter `$twilio_account_sid` and `$twilio_auth_token`.  You can find this on your account dashboard.  

![alt text](https://raw.githubusercontent.com/bmlt-enabled/yap/master/resources/twilio-auth-v2.png)

4.  You will need to ensure that the following `config.php` parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.  The user should be at the highest level of access in your BMLT hierarchy that you require access to.
   
```php
static $bmlt_username = "";
static $bmlt_password = "";
```

5. Be sure to get a Google Maps API key.  Specify this in config.php as the value for `$google_maps_api_key`.  Make sure you have "Google Maps Geocoding API" enabled on your credentials.  This key must be seperate from your BMLT key with no server restrictions, this is safe because yap never passes the key client side.  You can login into your Google API console here: https://console.cloud.google.com/apis/.  This article may be useful https://bmlt.magshare.net/google-maps-api-keys-and-geolocation-issues/.

6. Try testing that your application actually is functioning properly by opening a browser http://example.com/index.php.  

7. You will need to set up a Twilio account, and do the following:
    * Purchase a phone number (typically you would buy one for your locale, tollfree is pretty much unnecessary these days).
    * Configure that number to point to a Webook.  It would be something like https://example.com/index.php.

8. You can test whether or not you are properly configured by going to https://example.com/upgrade-advisor.php.

9. Make a call to your number and try it out.  If there is a problem the debugger in the Twilio console will let you know why.  Most likely you did not setup your config.php file correctly.
