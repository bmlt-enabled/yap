# Voice Greeting

---

The first thing callers hear is the **line title**, then the automated menu. Twilio does not host this script — Yap generates it from settings.

## Spoken title

`$title` is the name of the automated system, spoken with text-to-speech at the start of the call. The factory default is **Information Line**:

```php
static $title = "Information Line";
```

Set this in `config.php` to whatever your region calls the line — for example a service body name, or `"Helpline"` if you prefer that wording. You can also override it for one service body from **Service Bodies → Configure** by adding the `title` field.

`$title` is a required setting. After changing it, call your number to hear the new greeting. The admin **Settings** page shows the current value and source.

To change other spoken phrases (including volunteer notifications that still say "helpline"), see [Language Options](./language-options).

## Recorded greeting

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
