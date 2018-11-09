---
layout: default
title: Configuration Precedence
parent: General
nav_order: 3
---

# Configuration Precedence

---

It's possible to override most of the settings in several different ways.  There is a sequence of precedence as follows.  You can always validate the setting by going to the settings in the admin portal.

1) Querystring parameters that match the name.  For example if you wanted to override the title for one page you'd do the following: `index.php?title=something+here`
2) Session overrides.  This means the entire call will use this setting.  `index.php?override_title=something+here`.  Twilio will respect this setting for entire during of the call.
3) Config.php.  Any setting is controllable from within config.php.
4) Factory defaults.  You can review them on your `/admin/settings.php` page.
