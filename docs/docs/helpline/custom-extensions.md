---
title: Custom Extensions
sidebar_position: 2
---

---

It's possible to make custom extensions with the use of a few settings.

1. Add an option to your `$digit_map_search_type` that points to `SearchType::CUSTOM_EXTENSIONS`.
2. Add mappings in `$custom_extensions` for each extension you want to add. For example if you wanted to redirect extension "365" to a specific phone number you would do the below:

```php
static $custom_extensions = [365 => '555-555-1212'];
```

3. Add an option for the mp3 or wav file prompt that will play back all the choices in the custom extensions' menu `$en_US_custom_extensions_greeting` (be sure to specify recordings for each language that you offer).
To test, call in dial the digit map choice, and you should hear the audio file prompt playback. Enter the extension number followed by the pound sign (it might be good to inform the end-user in your prompt to press pound after they dial the appropriate extension).

Also to note is that the main menu greeting will not inform the user about the custom extensions option, so you may also want to set `$en_US_greeting` to include that information.
