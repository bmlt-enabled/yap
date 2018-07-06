# Release Notes

### 2.0.0 (unreleased)
* Admin portal.
* Music on hold for helpline call

### 1.3.3
* Added CAPTCHA to force dialing + made configuration options a little more flexible, this should prevent robocalls and fax dialers (ala Grasshopper).
* Improved setup documentation for Google API Keys + Search Radius on the BMLT.
* Added the location bias default to be US, improves search results.
* Making Helpline calls a prominent button on Facebook Messenger bot.
* Upgrading to Facebook Messenger API v3.0.
* Auto-pull hostname for host header async call for Facebook Messenger bot.

### 1.3.2
* Facebook Messenger bot responsiveness fix (avoids repeat message bug when response times out, long standing issue).
* Facebook Messenger bot remembers last searched location for day swaps.
* Facebook Messenger bot now returns helpline number if you type "talk".
* Minor change to CI workflow, not sending emails, slack only.

### 1.3.1
* Added the ability to change the outbound caller id to another verified Twilio number.
* Optional setting for force dialing message to indicate that the call was received and being processed.  (Useful for extension dialing with pausing) 

### 1.3.0
* Added option to skip location gathering for helpline routing.
* Playback of the Just For Today as a configurable option with a prompt.
* Added force dialing feature for wiring a Twilio number to just route somewhere else.

### 1.2.1

* Helpline to helpline redirection support.
* Added alternative authentication support `$alt_auth_method`, for non-SSL roots.
* Bug fix for tollfree bias configuration interpretation.
* Bug fix for <Pause> being inserted into SMS-only responses.

### 1.2.0

* Support for different postal code length expectations
* Location bias for international lookups
* Ignore formats option
* Added link to instructional video thanks @pjaudiomv
* Bug fix for unpublished meeting call routing

### 1.1.0

* New feature: language files with overrides, packaged with English and Pig Latin
* New feature: meeting start time grace period (default 15 minutes).  https://github.com/radius314/yap/issues/61
* Playback the Just For Today (hidden option right now)
* Documentation updates