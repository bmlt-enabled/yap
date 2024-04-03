# Venue Options

## Temporary Closures

If a meeting is marked with the TC format then it will be excluded from results. If it marked as a Virtual Meetings as well then it will be returned by with no physical address details.

If you want the text from the format description to be returned add TC to `include_format_details`. Example:

```php
static $include_format_details = ['TC', 'VM', 'HY'];
```

You can include any format here.  For example if you wanted to show whether a meeting is Open or Closed you could do that by including the format code in this setting.

If you want to change the description of some of the specific formats you can change the format description for that specific language in your root server.

## Virtual Meetings

If a meeting is marked as VM or HY with a format then you should be able to automatically have the virtual_meeting_link and phone_meeting_number returned in the SMS. If you want the links (for some reason), to be said in voice responses, you can enable this with say_links set to true. If you want the text from the format description to be returned add VM or HY to include_format_details. Example:

```php
static $include_format_details = ['TC', 'VM', 'HY'];
```

If you want to change the description of some of the specific formats you can change the format description for that specific language in your root server.
