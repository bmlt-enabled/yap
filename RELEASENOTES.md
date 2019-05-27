# Release Notes

### 3.2.0 (UNRELEASED)
* User name is now cached and stored in session. [#279]
* Database user authentication for global administrators 

### 3.1.1 (April 24, 2018)
* Italian translation
* Fix for bad XML encoding for Spanish JFT [#273]
* Fix for non-working SMS volunteer routing [#274]
* Fix for an edge case to render schedule ordering correctly [#162]
* Data point added to upgrade-advisor for getting the git hash for each build for QA and Beta users.
* Fix for duration not displaying properly on volunteer records. [#275]
* Fix for missing word in language files. [#276]

### 3.1.0 (April 18, 2019)
* Support for different voices for each languages [#260]
* Metrics drilldown by service body [#246]
* Minor tweaks to es-US translation [#262]
* Fix for prompts not being utilized from service body config overrides [#268]
* Fix for mobile check caching (it was being called once for each lookup)
* Fix for responder not working on some servers where the session cookie might be treated differently.

### 3.0.3 (April 8, 2019)
* Fix for overriden Twilio credentials that weren't being utilized.
* Renamed "Add" to "Create" for creating new groups.
* Fix for Twilio warnings messaging landlines. Added mobile_check option. [#171]

### 3.0.2 (April 4, 2019)
* Added validation for URL fields in Calling Handling. [#228]
* Added language file for Australian English to simplify configuration. [#252] [#253]
* Added configurable initial pause. [#244]
* Fixed forever loading spinner for single service body entitled user. [#263]
* Removed the word "today" in meeting listings, fixes translations issue. [#261]

### 3.0.1 (March 31, 2019)
* Fix for no map link being sent when using include map link option. [#254]
* Added validation for shift time selection to avoid impossible shifts. [#210]
* Fixes for fr-CA translations.

### 3.0.0 (March 23, 2019)
* Call blasting which gives the ability to call all on shift volunteers simultaneously. [#60]
* Language based call routing and volunteers.  This feature allow you to set up a list of volunteers that speak a particular language and route calls to them if that language is selected. [#146]
* Gender based volunteer routing. [#136]
* Shadow feature which all for tandem based training of volunteers. [#144]
* Include location_text field from the BMLT in meeting responses (SMS and Voice Response). [#172]
* Option to disable voice recognition (useful for demos in a noisy room). [#173]
* Volunteer groups, which allow for re-using schedules of volunteers and sharing to other service bodies. [#103]
* Service body based configuration overrides.  This allows you to override any setting in your config.php from within the admin UI.  For example, with this feature you can have multiple Twilio API keys on a single Yap instance. [#223]
* Volunteer calling records with details and duration. [#105]
* Voicemail links are accessible by each respective service body in calling records section. [#105]
* Voicemail can be routed now to any volunteer using the Responder setting. [#211]
* United States Spanish Translation.
* Reporting interface that displays daily numbers of action types. [#110]
* Display name visible now on the Admin Interface (if using the root server >= 2.13.5) [#96]
* New UI theme for Admin interface.
* The SMS Summary option and SMS ask option can now be used together. [#238]

### 2.5.4 (February 2, 2019)
* Added the ability to hide postal code lookups. [#231]
* Minor textual change SMS Summary Page SMS message.
* Added the ability to create custom CSS for SMS summary page.
* Bug fix for "tomorrow" lookups that return no meetings.

### 2.5.3 (January 31, 2019)
* SMS Summary Page responsiveness

### 2.5.2 (January 31, 2019)
* SMS Summary option [#230]

### 2.5.1 (November 18, 2018)
* Fix for results not being filtered out that have passed in time already for the day [#189]

### 2.5.0 (October 24, 2018)
* Custom Query support, return all meetings for example for an isolated geographical area, for instance an island. [#11]
* Meeting results are default sorted starting today and then looping through the week (configurable). [#168]
* Added list view button for schedule rendering.
* Fix on volunteer management single shift add not respecting times. [#167]
* Fix for session initialization issue happening on at least one server. [#165]
* Fix for "jft" SMS gateway responses that were too long and hitting the 1600 character limit. [#163]
* Fix for schedule sorting not being respected in calendar view (still a known issue with time sequencing taking precedence). [#162]

### 2.4.0 (October 18, 2018)
* Support for multiple service body contacts (CSV) for email notifications for voicemail.
* Support for multiple service body contacts (CSV) for SMS notifications for voicemail.
* Show enabled volunteers highlighted in light grey, making non-enabled ones more obvious visually. [#161]
* Added the ability to add 7 day shifts with the same time block.
* Made debug logging disabled by default and added additional logging messages.

### 2.3.3 (October 8, 2018)
* Fix for double usage of "components" in Google Maps API bias. [#157]
* Fix for dealing with bad input as a result of voice recognition on IVR inputs. [#155]

### 2.3.2 (October 2, 2018)
* Hotfix for broken SMS sending of meeting list information due to a Twilio bug. [#150]

### 2.3.1 (September 30, 2018)
* Fixed the voice prompt to "press or say" in conjunction with being able to speak responses on menus.

### 2.3.0 (September 30, 2018)
* Added "jft" response to SMS gateway (support for English, French, Spanish and Brazilian). [#147]
* You can now press or say any option in the IVR menus.  
* Postal codes support speech recognition which assists with letters in Canadian ones. [#142]
* Added French Canadian language support.
* Migrated retired <Sms> to new <Message> TwiML tag.

### 2.2.2 (September 21, 2018)
* Playback link of custom prompts on service body configuration modal. [#143]
* Bug fix for non-default timezone adding for volunteers on single shift entry. [#138]
* Bug fix for top results count when less than the result_count_max property. [#139]
* Regression bug fix for helpline lookup failures not handled properly.
* Retry workflow instead of a hangup when no more meetings found for today for a given lookup.

### 2.2.1 (September 13, 2018)
* Use Redirect twilio verb instead of header function for redirects. [#133]
* Fix for int'l numbers that were not auto-prepending "+" on SMS voicemail notification. [#137] 
* README table of contents fixes

### 2.2.0 (August 28, 2018)
* Introducing helpline SMS routing. [#46]
* Notes field added for each volunteer to help with various metadata. [#127]
* Blocklist for automatically rejecting specific calls or messages. [#125]
* Fix for int'l numbers that were not auto-prepending "+" on SMS volunteer notification. [#124]

### 2.1.3 (August 14, 2018)
* Fix for no volunteers specified in schedule edge case. [#122]
* Phone Numbers page removed from admin portal.
* Security patch to prevent logins from masquerading root servers. 

### 2.1.2 (Aug 4, 2018)
* Tomato helpline routing feature.
* Fixing several bad regressions introduced in 2.1.1 (mostly configuration related). [#116] [#117] [#118]

### 2.1.1 (Aug 3, 2018)
* Initial pause happens while gather is occurring, allows for extension dialing.
* Fix for SMS voicemail link as MP3 @pjaudiomv.
* Fix for disabling volunteer routing after being set.
* Fix for legacy error handling for not finding helplines in favor of newer method.
* Security patch to hide smtp settings that were exposed in Settings page on admin portal.
* Security patch to whitelist all settings (preventing insecure overrides) @DeathCamel58.
* Security patch to whitelist all languages (prevents directory hopping) @DeathCamel58.

### 2.1.0 (July 27, 2018)
* Email notifications with voicemail (see the README for more information) [#113]
* Voicemails are now MP3s to ensure reliable playback and delivery even in the lowest of bandwidth situations.
* Bug fix: Titles were being cut off.  There is now a 2 second delay to prevent this from happening.
* Bug fix: Authentication issue with special characters resolved.
* Bug fix: Voicemail was not working with Forced CallerID, this was resolved.
* Bug fix: Volunteer routing with no numbers will automatically go to voicemail. [#112]

### 2.0.1 (July 22, 2018)
* Bug fix: A schedule with no volunteers now automatically goes to voicemail. [#107]
* Bug fix: Better error handling for saving service bodies and volunteers. 
* Bug fix: An invalid zip code or location was not handling correctly, now it does. [#106]

### 2.0.0 (July 19, 2018)
* The "Admin" portal. Yap has the ability to make managing helpline volunteers very easy. If you are using helpline volunteer routing in Yap 1.x, you know how painful it was.
* The "Admin" portal re-uses the BMLT permission hierarchy scheme. This means if you sign into the Yap admin portal, that user will only see their service bodies. This makes it very easy to allow service bodies to administer their own volunteers. 
* Eliminated the "yap" in the Helpline field and "HV" formats for tagging things. The admin portal makes it simple to add volunteers and schedules.
* Music on hold while dialing out to a volunteer. In Yap 1.x it was dead air.
* README.md has a table of contents (makes it easier to find things), the documentation itself needs a cleanup.
* Talks directly to Twilio API. This is the just beginning of the ability to hook into some more powerful functionality. With this requires adding your Twilio credentials to config.php. You will notice in the Admin portal that you can see all your provisioned numbers very easily.
* Music on Hold for volunteer routing with playlist capabilities.
* Music on Hold Customizations, you can supply any MP3 file or Shoutcast/Icecast stream.
* Voicemail capabilities for volunteer routing.
* Optional SMS notifications for new voicemail.
* Customizable call cycling algorithms (Loop Forever, Cycle Once and go to Voice Mail, or Random Forever).
* Quick button for adding/removing 24/7 shifts for volunteers.
* Optional SMS notification for information volunteers of inbound calls (with Caller ID, for easy callbacks).
* Updates to the upgrade-advisor.php for checking common root server misconfigurations.
* Forced Caller ID setting (Yap 1.x feature parity)
* Redirect service body to another (Yap 1.x feature parity)
* Configurable per call timeouts (Yap 1.x feature parity)
* Various UI bug fixes, smoother modal transitions.
* Improved voice recognition failure handing.
* Call session configuration, defaults and overrides.
* Settings page for viewing the various options and their current value.
* Configuration precedence model.
* Multi-lingual phone menu + admin portal support.
* Direct to service helpline routing option.
* Custom mp3 prompts for initial greeting and voicemail.
* Facebook Messenger Bot split off to it's own application.

### 1.3.3 (July 6, 2018)
* Added CAPTCHA to force dialing + made configuration options a little more flexible, this should prevent robocalls and fax dialers (ala Grasshopper).
* Improved setup documentation for Google API Keys + Search Radius on the BMLT.
* Added the location bias default to be US, improves search results.
* Making Helpline calls a prominent button on Facebook Messenger bot.
* Upgrading to Facebook Messenger API v3.0.
* Auto-pull hostname for host header async call for Facebook Messenger bot.

### 1.3.2 (June 29, 2018)
* Facebook Messenger bot responsiveness fix (avoids repeat message bug when response times out, long standing issue).
* Facebook Messenger bot remembers last searched location for day swaps.
* Facebook Messenger bot now returns helpline number if you type "talk".
* Minor change to CI workflow, not sending emails, slack only.

### 1.3.1 (June 1, 2018)
* Added the ability to change the outbound caller id to another verified Twilio number.
* Optional setting for force dialing message to indicate that the call was received and being processed.  (Useful for extension dialing with pausing) 

### 1.3.0 (May 24, 2018)
* Added option to skip location gathering for helpline routing.
* Playback of the Just For Today as a configurable option with a prompt.
* Added force dialing feature for wiring a Twilio number to just route somewhere else.

### 1.2.1 (May 23, 2018)
* Helpline to helpline redirection support.
* Added alternative authentication support `$alt_auth_method`, for non-SSL roots.
* Bug fix for tollfree bias configuration interpretation.
* Bug fix for <Pause> being inserted into SMS-only responses.

### 1.2.0 (April 28, 2018)
* Support for different postal code length expectations
* Location bias for international lookups
* Ignore formats option
* Added link to instructional video thanks @pjaudiomv
* Bug fix for unpublished meeting call routing

### 1.1.0 (April 24, 2018)
* New feature: language files with overrides, packaged with English and Pig Latin
* New feature: meeting start time grace period (default 15 minutes).  https://github.com/radius314/yap/issues/61
* Playback the Just For Today (hidden option right now)
* Documentation updates

### 1.0.0 (April 11, 2018)

### Yap First Commit (April 15, 2017)
https://github.com/radius314/yap/commit/ead27730db78a002c318ccfc26f63a484f30a6a3

### bmlt-vox last commit (March 21, 2017)
https://github.com/radius314/bmlt-vox/commit/113531f38bb31ff765d26a69aef85b1d16a9cc1a

### bmlt-vox first commit (April 27, 2016)
https://github.com/radius314/bmlt-vox/commit/ef3616f5f11b043af4cac92a7ce8695530e1e705
