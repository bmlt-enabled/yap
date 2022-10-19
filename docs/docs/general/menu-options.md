# Menu Options

---

By default the menu sequence is as follows:

1. Find someone to talk to.
2. Find a meeting.
3. Just For Today (if enabled)
4. Dialback (if enabled)

These can all be customized.  For example if you wanted to swap the "Find someone to talk to" and "Find a meeting" options, you'd do the following below.

```php
static $digit_map_search_type => ['2' => SearchType::VOLUNTEERS, '1' => SearchType::MEETINGS, '3' => SearchType::JFT, '9' => SearchType::DIALBACK]
```
