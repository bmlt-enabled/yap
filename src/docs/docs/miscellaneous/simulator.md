---
title: Call/SMS Simulator
sidebar_position: 10
---

# Call/SMS Simulator (fake Twilio)

The simulator exercises yap's phone and SMS flows end to end, over real HTTP, against a running instance, without placing real Twilio calls or sending real SMS. It is a development and CI tool. Nothing about it is enabled on a live helpline.

It has two halves:

- **Fake Twilio mode** (`TWILIO_FAKE_MODE=true`) on the yap instance. Yap's Twilio REST client is built on a transport that records every outbound API request (calls placed to volunteers, SMS sent, calls redirected or hung up, conference and number lookups) in the database instead of sending it to `api.twilio.com`, and answers with plausible Twilio responses. The other side effects of a call stay on the box too:
  - Recording downloads from `api.twilio.com` (the voicemail email attachment) are answered with placeholder audio. Each download is recorded with how it authenticated.
  - Email (the voicemail notification) is recorded instead of sent.
  - The Just for Today and Spiritual Principle a Day come from the pages recorded in `tests/Fixtures/http/readings/`, not na.org. A build without that directory, such as the release zip, fetches them live.
- **`php artisan yap:simulate`** plays Twilio's side. It sends Twilio-shaped webhooks signed with `X-Twilio-Signature`, executes the TwiML that comes back (following `<Redirect>`, answering `<Gather>` with scripted digits, joining `<Conference>`s, answering or ignoring volunteer calls), and asserts on what callers hear and on the captured outbound Twilio requests.

Because it goes through the web server, it covers what the in-process Pest tests can't: routing and rewrites, the absolute callback URLs yap puts in TwiML, session cookies carried across the webhooks that make up one call, signature validation, and real BMLT root server lookups.

## Running it locally

1. Start a database. `docker compose -f docker/docker-compose.yml up -d` provides MySQL on port 3106, which matches `src/config.test.php`. On an empty database, run `ENVIRONMENT=test php artisan migrate --force` from `src/` once.
2. Start yap in fake mode with signature validation on and a session driver that persists between requests:

   ```bash
   cd src
   ENVIRONMENT=test TWILIO_FAKE_MODE=true TWILIO_DISABLE_SIGNATURE_VALIDATION=false \
     SESSION_DRIVER=file APP_URL=http://127.0.0.1:8000 \
     php artisan serve --host=127.0.0.1 --port=8000
   ```

3. In another shell, run the scenarios:

   ```bash
   cd src
   ENVIRONMENT=test php artisan yap:simulate --base-url=http://127.0.0.1:8000
   ```

   The command signs requests with `twilio_auth_token` from the same `config.php`. Use `--auth-token` when the target uses a different one.

Add `-v` to see every webhook, every `<Say>`, and every digit pressed. The command exits non-zero if any scenario fails, so it can gate CI.

## Scenarios

`php artisan yap:simulate --list` shows them. With no arguments, every scenario runs. Name one or more to run just those: `php artisan yap:simulate dialback readings`.

Each scenario asserts on what the caller hears (every `<Say>`) and on the Twilio requests yap makes. Settings a scenario needs for one call, such as `sms_ask` or `language_selections`, ride on that call's first webhook as `override_*` parameters, so they don't change the instance.

### Meeting search and the menus

| Scenario | What it does |
|---|---|
| `meeting-search` | Caller presses 2 (meetings), 2 (zip code), keys in `--location`, and must hear meeting results. The results must also go out by SMS. |
| `sms-meeting-search` | Texts `--location` to the SMS gateway. Meeting results must come back as outbound SMS. |
| `meeting-search-options` | Zip code search through `address-lookup`. With the default `meeting_result_sort`, today's meetings must be read before tomorrow's. With it set to tomorrow's weekday, tomorrow's come first. With `meeting_search_radius=5` (miles), nothing may be found. Every webhook carries a `Timestamp` that pins "today" to a Tuesday, so the run doesn't depend on the clock. |
| `voice-location-search` | Meeting search by voice. The caller says a city (`city-or-county-voice-input`), says the province first (`province_lookup`, `province-voice-input`), or presses the province's number (`province_lookup_list`, `province-lookup-list-response`). Each must reach `address-lookup` with "city, province" and read results. |
| `language-selector` | With `language_selections=en-US,es-US`, `lng-selector` must offer each language in that language. After pressing 2, the menu and the meeting results must be in Spanish. |
| `post-call-sms` | With `sms_ask`, nothing may be texted until the caller asks at `post-call-action`. Pressing 1 must text the results and say goodbye. With `sms_combine` and `infinite_searching`, pressing 3 must send one combined SMS and return to the menu. |
| `readings` | With `jft_option` and `spad_option`, menu option 3 must read the Just for Today and option 4 the Spiritual Principle a Day, through to the copyright line. Option 3 on the meeting search location menu must also read the JFT. Yap has no SMS path for the readings. |
| `custom-extensions` | A menu digit mapped to custom extensions must reach `custom-ext`, play the extensions greeting, and `custom-ext-dialer` must dial the extension's number from the helpline's own number. |
| `blocklist` | A caller on `blocklist` must be rejected (`<Reject/>`) without hearing anything. Any other caller must reach the menu. |
| `no-coverage-fallback` | A helpline search from a zip code with no coverage (28401, Wilmington, NC) must dial `fallback_number`. Without one, the caller must be told there is no coverage. |

### Helpline

These need a service body (`--service-body`). They configure its call handling and volunteers with `--seed`, so they are skipped when every scenario runs without those options.

| Scenario | What it does |
|---|---|
| `helpline-volunteer` | Caller presses 1 and is held in a helpline conference. Yap must dial a volunteer. With `--outcome=answer` the volunteer presses 1 and joins the caller. With `--outcome=no-answer` the volunteer never picks up, and the caller must be redirected to voicemail. |
| `gender-routing` | Under each of the five call strategies, the caller is asked who they want to talk to. Pressing 2 must dial only the woman volunteer. Pressing 3 (`gender_no_preference`) may dial anyone: the first volunteer under the linear strategies, all three when blasting. |
| `call-blasting` | The blasting strategy must ring all three volunteers at once. The first to answer and press 1 joins the caller. One who answers later must be told a volunteer already joined and be hung up on. One who never answers must not send the caller to voicemail. |
| `caller-hangup-before-accept` | The caller hangs up after the volunteer answers but before they press 1, and again before they answer at all (#1644). Both times the volunteer must hear that the caller hung up and be hung up on. |
| `voicemail-notifications` | No volunteer answers, the caller leaves a message, and Twilio delivers the recording to `voicemail-complete`. The primary contact and the on-shift responder volunteer must be texted a link to the signed `/media` proxy (#1664), not the raw Twilio URL, with a dialback PIN. The primary contact must be emailed the same link, with the recording attached. The attachment must be downloaded from Twilio with Basic Auth using the account SID and auth token. The link must play, and a tampered signature must get a 403. |
| `dialback` | Yap dials a volunteer, who misses the call but gets an SMS with a dialback PIN (`sms_dialback_options=1`). Calling in, pressing 9 and entering the PIN must dial the original caller from the helpline's number. A wrong PIN must not dial anyone. |
| `sms-helpline` | Texting `talk <location>` must route to the service body covering `--latitude`/`--longitude` (`volunteers_and_sms`). The texter must be told the request was received. Every SMS volunteer must be texted the texter's number, and a phone-only volunteer must not. `talk` alone must get formatting instructions. SMS routing goes by coverage, not `--service-body`, so `--seed` configures whichever service body the root server says covers the location. |

### Location without a Google Maps key

By default the scenarios pin `--location` (zip `14456`) to `--latitude`/`--longitude`/`--timezone` with `override_custom_geocoding` and `override_timezone_default` on the first webhook. No Google Maps key is needed, but the BMLT lookup is still real. Pick coordinates your root server has meetings near. Pass `--real-geocoding` to send the location to Google's geocoding and time zone APIs instead.

`meeting-search-options` expects no meetings within 5 miles of the coordinates, and `no-coverage-fallback` uses a fixed location with no helpline coverage on the test root server.

### Seeding

When the simulator runs against an instance that shares its database (local, CI), `--seed` writes what each helpline scenario needs before it runs. That is a call strategy and all-day volunteers, plus a primary contact for voicemail and SMS volunteers for `sms-helpline`. `--seed` replaces only the call handling and volunteers of the service body it configures, and is refused in production. Each scenario seeds its own configuration, so scenarios can run in any order.

```bash
php artisan yap:simulate --base-url=http://127.0.0.1:8000 --service-body=1006 --seed
php artisan yap:simulate helpline-volunteer --base-url=http://127.0.0.1:8000 --service-body=1006 --seed --outcome=no-answer
```

Against an instance with its own database, configure the service body by hand and leave out `--seed`. `helpline-volunteer` then uses whatever volunteers are on shift. The others need the configuration they would have seeded.

### Email

`voicemail-complete` only emails when `smtp_host` is set. `config.test.php` sets the SMTP settings whenever the server runs with `TWILIO_FAKE_MODE=true`. In fake mode the message is recorded rather than sent, so the host is never contacted. Against another configuration, set the `smtp_*` settings, or `voicemail-notifications` fails and says why.

## Inspecting captured Twilio requests

In fake mode, yap exposes control endpoints under `/fake-twilio/`. They are signed like every other Twilio route.

| Endpoint | Purpose |
|---|---|
| `GET /fake-twilio/requests?after=<id>&resource=<name>` | Captured outbound requests, oldest first. `resource` is one of `calls.create`, `calls.fetch`, `calls.update`, `messages.create`, `conferences.read`, `participants.read`, `incoming_phone_numbers.fetch`, `incoming_phone_numbers.read`, `recordings.delete`, `lookups.fetch`, or `unknown` for anything yap does not model (answered with a Twilio-style 404). Two are not Twilio REST calls: `recordings.media` is a recording download, with `auth`, `username` and `credentials_match` showing how it authenticated, and `email.send` is an email, with its recipients, subject, bodies and attachment sizes. |
| `POST /fake-twilio/reset` | Clear captured requests and fake call/conference state. |
| `POST /fake-twilio/calls` | Register an inbound call (`sid`, `from`, `to`), or change a known call's `status`. |
| `GET /fake-twilio/calls/{sid}` | A call's fake state. |
| `POST /fake-twilio/conferences/join` and `/leave` | Add or remove a participant (`friendly_name`, `call_sid`). |

The same data is in the `fake_twilio_requests`, `fake_twilio_calls`, and `fake_twilio_conference_participants` tables.

## Notes

- `TWILIO_FAKE_MODE` is ignored when `APP_ENV=production`, the same as `TWILIO_DISABLE_SIGNATURE_VALIDATION`. A production deployment can never silently drop calls or SMS.
- Some absolute callback URLs are built with `https://`. Against a plain `http://` base URL, the simulator sends callbacks on the target's own host with the base URL's scheme, and logs it with `-v`.
- The scenarios assume the default menu digits (`digit_map_search_type` and `digit_map_location_search_method`) and English prompts. `custom-extensions` maps its own digit for the call.
- The Pest suite runs the same scenarios in-process (`tests/Feature/Simulator`) through the full middleware stack, so they stay in step with the code.
