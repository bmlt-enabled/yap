---
slug: pr-extension-hack
title: Hacking Yap to Add a PR extension
authors: [dgershman]
tags: [formats, yap]
---

# Hacking Yap to Add a PR extension

You can add a Public Relations or other miscellaneous extensions by doing the following, and leveraging the [Menu Options](/general/menu-options/) and the [Custom Extensions](/helpline/custom-extensions/) option.

<!-- truncate -->

1. Update or set a digit map as follows adding a new option.  As see below we added a new option for pressing 3.

```php
static $digit_map_search_type = ['1' => SearchType::MEETINGS, '2' => SearchType::VOLUNTEERS, '3' => SearchType::CUSTOM_EXTENSIONS];
```

2. You will have to add a voice recording that states what all the options are because by default the custom extension is a hidden menu option in that the spoken voice feature will not indicate it is there.  You set this by placing an MP3 or WAV file on a webserver and referencing it with [this setting](/general/voice-greeting/).

3. You will create a new setting that indicates what each custom extension is and what phone number it goes to.  For example if you wanted extension 1 to go to 555-555-1212 you would set this as below.

```php
static $custom_extensions = [1 => '555-555-1212'];
```

4. Once that is done you will create another voice recording indicating what each custom extension will route to.  You can include as many as you want.  The important thing to note is that your custom recording should inform the caller to press pound after they enter the extension number.

```php
static $en_US_custom_extensions_greeting = "https://example.org/customext.mp3";
```
