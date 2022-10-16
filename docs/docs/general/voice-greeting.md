# Voice Greeting

---

It's possible to record a custom voice prompt and have it play back instead of the traditional voice engine.  Set the following:

*Keep in mind that this will override the main menu as well, so you should record the relevant prompts (i.e. press 1 to find someone to talk too... press 2 to find a meeting)

```php
static $en_US_greeting = "https://example.com/your-recorded-greeting.mp3"
```

You can also set a custom greeting for voicemail.

```php
static $en_US_voicemail_greeting = "https://example.com/your-recorded-greeting.mp3"
```

These settings are overridable from within each service body call handling.
