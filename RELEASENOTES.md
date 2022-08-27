# Release Notes

### 4.2.0 (UNRELEASED)
* Yap now requires PHP version 8.0 and higher. [#623]
* Added support for downloading the list of volunteers in JSON format for a given service body. [#612]
* Added support for customizing SMS for incoming calls. [#636]
* Show missed calls also as a percentage of total calls received.
* Metric endpoint now returns volunteer missed and answered metrics, not exposed to UI yet. [#632]
* Fixed issue when metric summaries wouldn't zero out between switching service bodies.
* Fixed issue with metric summary for Volunteer SMS which wasn't reporting correctly.
* Fixed issues with unassigned aka "General" metrics, when no service body is known or defined at a point in a call or SMS. [#627]
* Fixed an issue with reports not recursing more than one level. [#620]

### 4.1.2 (August 17, 2022)
* Fixed an issue where leading whitespace could cause corrupted xml output resulting in failed calls. [#621]

### 4.1.1 (August 14, 2022)
* Use the current logged-in users' settings for the Admin Portal.  This suppresses the Twilio error for Zonal Yap Servers for service bodies. [#614]
* Missed call summary metric for reports [#611]
* Fix for missing additional volunteers on reports regression. [#613]

### 4.1.0 (August 6, 2022)
* Added date picker for reports. [#574]
* Added support for downloading the list of volunteers for a given service body. [#595]
* Added support for SMS reporting and metrics. [#574]
* Added metrics points for when there is no data. [#530]
* Added reporting metrics summary feature with some basic metrics. [#574]
* Fix for data missing for meeting lookups. [#594] [#586]
* Fix for loading map points on reports.  Rendering enhanced.
* Fix to handle metrics and reporting bugs for when service body tracking fails. [#530]
* Fix for how metrics service body id lookup happens. [#530] 
* Fix for upgrade advisor regression. [#589]
* Fallback routing for when helpline field was never set and volunteer routing is disabled.

### 4.0.4 (February 18, 2022)
* Fix for language fallback for volunteers.  It will now correctly use the first language in `language_selections` if none are selected for each volunteer.  Otherwise it will use `language`.

### 4.0.3 (December 25, 2021)
* Fix for additional scenarios where voicemail deletion would not complete sucessfully. [#576]

### 4.0.2 (December 18, 2021)
* Fix for allowing deletions of voicemail for native admin users. [#576]   
* Fix for anonymous numbers (+266696687) that are attemping to receive SMS, they will not be able to per Twilio's API rules. [#571]  
* Added note to explain the limitation in using greetings with Call Handling vs Service Body Configuration and Precedence. 

### 4.0.1 (November 28, 2021)
* Fix logouts there were not completing for BMLT based users. [#563]  
* Fix for extra prompt bypass when disabling postal code gathering. [#568]
* Group management page optimizations and fixes.
* Translations for gender prompts were not overridable.
* Autocomplete for username / password forms.

### 4.0.0 (October 16, 2021)
* Voicemail deletion feature. [#528]
* Gender routing with no preference option. [#523]
* Geocoding overriding feature allows for specifying locations with latitude and longitude to override Google Geocoding lookups [#552]
* Minor report formatting enhancements including some Metadata column text wrapping and using tooltips.
* Fix for unspecified language volunteers not correctly using fallback language setting.  The new fallback is whatever the first setting is in the `language_selections` setting.  [#507]
* Fix for music on hold not inheriting from redirected service body volunteer routing. [#532]
* Fix for groups page dropdown when no service body is selected. [#505]
* Fix for gender selection for service body overrides.
* Fix for gender selection response always in English. [#506]
* Fix for volunteer SMS routing (no support for Gender or Language options at this time). [#508]
* Fix for servers that would not accept POST for Music On Hold, now requires GET.
* Fix for primary contact number not being shown on service body configuration screens for SMS volunteer routing. [#509]
* Fix for database related edge case for null values in service body configuration.
* Fix for recursive metrics not being shown. [#511]
* Migration to Laravel Framework. [#483]

### 3.10.0 (October 3, 2021)
* Fix for faulty tomato helpline routing when service body ID collisions occur between local root server and tomato. [#546]

### 3.9.9 (November 23, 2020)
* Fix for is_admin db column on some systems not being set as PHP boolean. [#486]

### 3.9.8 (November 21, 2020)
* Fix for gender selected voice menu.
* Instructions for de-selecting items in the Users management modal. 

### 3.9.7 (October 24, 2020)
* Bug fix for incorrect user-agent header.

### 3.9.6 (October 24, 2020)
* BMLT auth (yap auth v1) session cookies no longer stored on disk. [#309]
* Use GET requests instead of POST requests for BMLT user auth verification and logouts [#485]
* Changes to cache rules that are more sane.
* Auto pruning of expired cache entries [#474]
* Yap based administrators the ability from the Settings page on the admin UI to clear database cache.

### 3.9.5 (September 27, 2020)
* Fix for regression in some call events. [#478]
* Fix for caching response times. [#474]

### 3.9.4 (September 16, 2020)
* Performance tuning for reports.
* Documentation url fixes.

### 3.9.3 (September 4, 2020)
* Added `virtual_meeting_additional_info` as part of spoken response for meetings searches with formats `TC` and `VM` or `HY`.  [#465]
* Added a fix for `disable_postal_code_gather` which allows it to work independent of `jft_option` settings.
* Fix for missing `<?php` tag on installer. [#463]

### 3.9.2 (August 8, 2020)
* Fix for issue where service body configuration precedence was only considering one level [#460]
* Added cache clearing endpoint.

### 3.9.1 (July 29, 2020)
* Fix for some webservers which were not rendering meeting search results page correctly. [#457]

### 3.9.0 (July 27, 2020)
* Added the `include_format_details` option which can return details such as information about meetings being Virtual, Hybrid, and/or Facility is Temporarily Closed.
* Added parent service body names and IDs for service body selection.
* Saying links (`say_links`) is now an option which is disabled by default.
* User modal titling fixes.
* Fixes for metrics reports and performance improvements.

### 3.8.2 (July 25, 2020)
* Fixes for terminology refactor. [#451]

### 3.8.1 (July 21, 2020)
* Fix for SMS summary page javascript issue. [#449]

### 3.8.0 (July 19,2020)
* Dark mode theme for Admin UI.
* Admin UI for management of non-BMLT users. [#340]
* State/province lookup by digit menu. [#422]
* Added check for semanticAdmin in the upgrade advisor. [#431]
* Bundles croutonjs from npmjs.

### 3.7.5 (July 14, 2020)
* Fix for infinite Searching option which did not consider initial webhook. [#446]

### 3.7.4 (May 20, 2020)
* Support for HY format.

### 3.7.3 (May 12, 2020)
* Fix for hamburger menu not working on admin UI [#419]

### 3.7.2 (May 5, 2020)
* Normalize timeouts for prompts. [#418]

### 3.7.1 (May 1, 2020)
* Added database caching for externals HTTPS request for improving performance.
* Added a simple ping page for load balancer health checks.
* Zip package now extracts into a folder.

### 3.7.0 (April 16, 2020)
* Introducing Yap installer. [#373]
* Added custom extensions feature that allows for setting up arbitrary call forwards and/or prompt playbacks. (https://github.com/bmlt-enabled/yap/wiki/Custom-Extensions) [#355]
* Added an option to combine SMS messages into a single SMS versus individual ones.
* Added virtual links for those meetings tagged with VM for location-centric virtual meetings.
* Added support for temporary closures format which hides physical addresses if virtual link is present.
* Added configuration option to include unpublished meetings.
* Added time format customization option.
* Added meeting results voice suppression option.
* Added croutonjs as meeting summary page for SMS.
* Added the ability to call in and listen to voicemail messages. [#384]
* Added map link language support.
* Performance improvements for reports.
* Fixed encoding issues with JFT. [#414]
* Dropping support for Internet Explorer 11. [#389]

### 3.6.4 (February 4, 2020)
* Fix for meeting playback being calculated as UTC timezone.

### 3.6.3 (January 30, 2020)
* Added auto selecting of service body when only one is available to current user. [#377]
* Added service body announcement option for volunteer routing. [#382]
* Changed default menu option for dialback to 9.
* Fix for speech gathering option. [#372]
* Fix for aligning metrics summary colors with map pins for reports. [#370]
* Fix to prevent Twilio credentials from being overridden mid-call. [#375]
* Fix for SMS summary page map link not working on some Android phones. [#380]

### 3.6.2 (January 18, 2020)
* Added simple check for state/province in SMS meeting search. [#369]
* Added toggle switch for recursively running reports [#365]
* Added request caching to improve performance. 
* Fix for filtering out services bodies you don't have rights to in "All" mode for reports [#364]
* Fix for tomato meeting searches on by default.

### 3.6.1 (January 5, 2020)
* Fix for null coordinate responses in map data. [#363]
* Fix for service body name in reports.

### 3.6.0 (January 4, 2020)
* Added favicon.
* Added mapping of call events with POI CSV downloads. [#353]
* Added call event for helpline routing.
* Added setting for using Tomato for meeting searches. [#357]
* Added the ability to add distance results to SMS. [#358]
* Added alerting capability, implemented for cases where reporting webhook not set. [#341]
* Added the service body name in the call events formatting.
* Changed metrics legend to match map legend.

### 3.5.5 (December 25, 2019)
* Fix in regression for status.php datetime formatting.

### 3.5.4 (December 25, 2019)
* Call records are paginated now. [#338]
* Performance improvements for reports.

### 3.5.3 (December 19, 2019)
* Voicemail play link is now an MP3. [#351]

### 3.5.2 (December 15, 2019)
* Fix for large data responses being truncated by MySQL. [#350]

### 3.5.1 (December 14, 2019)
* Added the ability to disable BMLT based authentication in favor of local auth. [#339]
* Added service body IDs in dropdowns on the admin UI. [#349]
* Several reports fixes. [#342] [#343] [#344] [#345] [#346] [#348]
* Fix for Play link not working on Call Handling modal for service bodies. [#347]

### 3.5.0 (December 4, 2019)
* Added database driven call detail records. [#289] [#285]
* Added the ability to "dialback". [#209]
* Added the ability to have custom filters for call routing. [#314]
* Added the ability to random cycle once, and then go to voicemail. [#310]
* Added the ability to have a volunteer answer a call without pressing 1. [#321]
* Added new and improved call blasting with volunteer detection. [#321]
* Added voicemail for call blasting. [#332]
* Added improved routing logic for Tomato based helpling routing [#329]
* Added support for custom campaigns to track metrics down to the phone number level. [#301]
* Added a new setting for the default language for volunteers when none is set.
* Added support for non-BMLT non-admin users.
* Pointing to tomato.bmltenabled.org now for 🍅 things.
* Fix for Language Volunteer routing not working [#325]
* Fix for SMS not properly higlighting addresses for app maps. [#317]
* Fix for SMS caller ID when Forced Caller ID is set. [#307]
* Fix for Helpline Redirect not working on a stock installation.
* Fix for digit map display in Settings in administration portal.
* Fix for Yap 2.x import for single quoted characters. [#318]
* Fix for some JFTs that had an ampersand.
* Fix for potential link blocking on SMS summary links using link rewrites [#328]
* Removed `helpline_search_unpublished` feature which was no longer in use.

### 3.4.0 (September 8, 2019)
* Service body direct dial by ID. [#302]
* Remappable digits for search types. [#292]
* Remappable digits for location search method.
* Allow for a title customization of the login screen. 
* Toll number bias. [#291]
* Query performance improvements. [#284]
* Tomato URL is overridable.
* Fix looping voicemail recordings [#300]
* Fix for JFT Option not disabling JFT text or voice. [#287]
* Fix for documentation deep link 404s. [#303] 
* Fix for Canadian French JFT not working.

### 3.3.1 (June 21, 2019)
* Disable speech recognition menus by default.

### 3.3.0 (June 18, 2019)
* Logo header on the login screen and version number display.
* Packaging improvements to reduce the size of the deployment (always packaging mainline deps).

### 3.2.0 (June 5, 2019)
* User name is now cached and stored in session. [#279]
* Call records are displayed in your local browser timezone now. [#282]
* Better handling of abbreviations with some textual cues. [#219]
* Database user authentication for global administrators.

### 3.1.1 (April 24, 2018)
* Italian translation
* Fix for bad XML encoding for Spanish JFT. [#273]
* Fix for non-working SMS volunteer routing. [#274]
* Fix for an edge case to render schedule ordering correctly [#162]
* Data point added to upgrade-advisor for getting the git hash for each build for QA and Beta users.
* Fix for duration not displaying properly on volunteer records. [#275]
* Fix for missing word in language files. [#276]

### 3.1.0 (April 18, 2019)
* Support for different voices for each languages. [#260]
* Metrics drilldown by service body. [#246]
* Minor tweaks to es-US translation. [#262]
* Fix for prompts not being utilized from service body config overrides. [#268]
* Fix for mobile check caching (it was being called once for each lookup).
* Fix for responder not working on some servers where the session cookie might be treated differently.

### 3.0.3 (April 8, 2019)
* Fix for overridden Twilio credentials that were not being utilized.
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
