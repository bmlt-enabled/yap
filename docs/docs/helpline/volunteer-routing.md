# Volunteer Routing

---

Incompatible with Yap 1.x Volunteer Dialers, you will have reconfigure your setup.

1) You will need to ensure that the following `config.php` parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.

```php
static $bmlt_username = "";
static $bmlt_password = "";
```

2) You will need to specify Twilio API parameters.  You can find this on your account dashboard when you login into Twilio.

```php
static $twilio_account_sid = "";
static $twilio_auth_token = "";
```

3) Head over to your admin login page.  https://your-yap-instance/admin.
4) Login with any credentials from your BMLT server.
5) Go to the Service Bodies tab and click "Configure".  From there you should see a check box to enable Volunteer Routing.  Check it off and save.
6) Go to Volunteers, and you should see that service body in the dropdown, and select it.
7) Click Add Volunteer.  Fill out the Name field, and then click the "+" to expand out the rest of the details.  You should be able to start populating the number and shift information.  You will also have to click "Enable" in the bottom right.  Once you are done, click "Save Volunteers".
8) You can also sort the sequence by dragging and dropping the volunteer cards.
9) Go to Schedules to preview your changes.  Select your service body from the dropdown, and it should render onto the calendar.
10) You can now test to see if things are working.

    * Volunteer Routing Redirect: You do this by setting in the Service Body Call Handling the Volunteer Routing mechanism to "Volunteers Redirect" and specifying the respective Service Body Id in the "Volunteers Redirect Id" field.
    * Forced Caller Id: This setting changes the outgoing display caller id.
    * Call Timeout: This is the number of seconds before trying the next number for volunteer routing.
