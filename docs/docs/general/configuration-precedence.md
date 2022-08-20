---
sidebar_position: 3
---

# Configuration Precedence

---

It's possible to override most of the settings in several different ways.  There is a sequence of precedence as follows.  You can always validate the setting by going to the settings in the admin portal.

1) Querystring parameters that match the name.  For example if you wanted to override the title for one page you'd do the following: `index.php?title=something+here`

2) Session overrides.  This means the entire call will use this setting.  `index.php?override_title=something+here`.  Twilio will respect this setting for entire during of the call.

3) **New Yap 3.0**  Service body overrides from the Admin UI.  These have a cascading hierarchy of precedence based off the service body above it in the hierarchy tree from your root server.  Either the `override_service_body_id` (which will affect call routing) or `override_service_body_config_id` will pull these settings into the session for use.

4) Config.php.  Any setting is controllable from within config.php.

5) Factory defaults.  You can review them on your `/admin/settings.php` page.

You can completely override any `config.php` file setting as well with additional precedence.

1) Create a new file called `config_something.php`.  Add whichever settings you want to override.  You do not need every setting, only those you want to override.
2) Use the last part after the underscore in your webhook as, for example: https://your-yap-server/index.php?override_config=something.
