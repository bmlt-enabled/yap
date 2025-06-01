# Language Options

---

There is a concept of language resource files.  You will notice them in the `app/Structures/Localizations.php` file.  Please open a ticket if you would like to contribute to translating to another language.

You can also override any of the language prompts in the `config.php` file. 

For example, say you wanted to still use English, but change the "city or county" prompt to say, "city or suburb".  You would do the following in config.php:

```php
static $override_city_or_county = "city or suburb";
```

You can override by specific language as well.  This is required when using multiple languages.

```php
static $override_fr_CA_city_or_county = "blah blah blah";
```

You can also change the spoken language accent.  There is a wide variety.  See the Twilio documentation for more details: [https://www.twilio.com/docs/voice/twiml/say#attributes-language](https://www.twilio.com/docs/voice/twiml/say#attributes-language).  There are also some additional voices available here as well [https://www.twilio.com/docs/voice/twiml/say/text-speech#voices](https://www.twilio.com/docs/voice/twiml/say/text-speech#voices).

An example would be using an Australian English Accent.  Set your config.php to:

```php
static $voice = "alice";
static $language = "en-AU";
``` 

### Language Call Routing

You can also create a language selection menu upon dialing in.  It will only be available for those that there are resource files for in `lang/` folder.  If you have some translations, please send them, so they can be merged in.

Add a new setting called, specifying the language codes for each language you want included.  The order will indicate the order in which it will be played back:

```php
static $language_selections = "en-US,pig-latin";
```

This example will make option 1, English and option 2, pig latin.

You can also tag volunteers ahead of time before enabling the option by using this setting.  Either option will work, except this one will not enable the voice prompt menu.

```php
static $language_selections_tagging = "en-US,pig-latin";
```

It may also make sense if you want to indicate the name of the service body or helpline they are calling to record a new prompt.  Keep in mind to indicate all the menu options for each language as there will be no automated menu once this setting is enabled.

```php
static $language_selections_greeting = "https://example.org/test.mp3";
```

### Mixing languages and voices

Voices can be configured for every language option.  For example for Spanish:

```php
es_US_voice = "Polly.Penelope";
```

If you want to route calls to volunteers by language, see the section on Language in [Specialized Routing](../../helpline/specialized-routing/).
