---
title: Call/SMS Simulator
sidebar_position: 10
---

# Call/SMS Simulator (fake Twilio)

The simulator exercises yap's phone and SMS flows end to end, over real HTTP, against a running instance, without placing real Twilio calls or sending real SMS. It is a development and CI tool. Nothing about it is enabled on a live helpline.

It has two halves:

- **Fake Twilio mode** (`TWILIO_FAKE_MODE=true`) on the yap instance. Yap's Twilio REST client is built on a transport that records every outbound API request (calls placed to volunteers, SMS sent, calls redirected or hung up, conference and number lookups) in the database instead of sending it to `api.twilio.com`, and answers with plausible Twilio responses.
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

`php artisan yap:simulate --list` shows them. With no arguments, every scenario runs.

| Scenario | What it does |
|---|---|
| `meeting-search` | Caller presses 2 (meetings), 2 (zip code), keys in `--location`, and must hear meeting results. The results must also go out by SMS. |
| `sms-meeting-search` | Texts `--location` to the SMS gateway. Meeting results must come back as outbound SMS. |
| `helpline-volunteer` | Caller presses 1 and is held in a helpline conference. Yap must dial a volunteer. With `--outcome=answer` the volunteer presses 1 and joins the caller. With `--outcome=no-answer` the volunteer never picks up, and the caller must be redirected to voicemail. |

### Location without a Google Maps key

By default the scenarios pin `--location` (zip `14456`) to `--latitude`/`--longitude`/`--timezone` with `override_custom_geocoding` and `override_timezone_default` on the first webhook. No Google Maps key is needed, but the BMLT lookup is still real. Pick coordinates your root server has meetings near. Pass `--real-geocoding` to send the location to Google's geocoding and time zone APIs instead.

### Volunteer routing

`helpline-volunteer` needs a service body with volunteer routing enabled and a volunteer on shift right now. Pass it with `--service-body`. When the simulator runs against an instance that shares its database (local, CI), `--seed` creates both: a "linear cycle then voicemail" call strategy and one all-day volunteer at `--volunteer` (default `+15555551234`). `--seed` replaces only that service body's call handling and volunteers.

```bash
php artisan yap:simulate helpline-volunteer --base-url=http://127.0.0.1:8000 --service-body=1006 --seed
php artisan yap:simulate helpline-volunteer --base-url=http://127.0.0.1:8000 --service-body=1006 --seed --outcome=no-answer
```

## Inspecting captured Twilio requests

In fake mode, yap exposes control endpoints under `/fake-twilio/`. They are signed like every other Twilio route.

| Endpoint | Purpose |
|---|---|
| `GET /fake-twilio/requests?after=<id>&resource=<name>` | Captured outbound REST requests, oldest first. `resource` is one of `calls.create`, `calls.fetch`, `calls.update`, `messages.create`, `conferences.read`, `participants.read`, `incoming_phone_numbers.fetch`, `incoming_phone_numbers.read`, `recordings.delete`, `lookups.fetch`, or `unknown` for anything yap does not model (answered with a Twilio-style 404). |
| `POST /fake-twilio/reset` | Clear captured requests and fake call/conference state. |
| `POST /fake-twilio/calls` | Register an inbound call (`sid`, `from`, `to`), or change a known call's `status`. |
| `GET /fake-twilio/calls/{sid}` | A call's fake state. |
| `POST /fake-twilio/conferences/join` and `/leave` | Add or remove a participant (`friendly_name`, `call_sid`). |

The same data is in the `fake_twilio_requests`, `fake_twilio_calls`, and `fake_twilio_conference_participants` tables.

## Notes

- `TWILIO_FAKE_MODE` is ignored when `APP_ENV=production`, the same as `TWILIO_DISABLE_SIGNATURE_VALIDATION`. A production deployment can never silently drop calls or SMS.
- Some absolute callback URLs are built with `https://`. Against a plain `http://` base URL, the simulator sends callbacks on the target's own host with the base URL's scheme, and logs it with `-v`.
- The scenarios assume the default menu digits (`digit_map_search_type` and `digit_map_location_search_method`) and English prompts.
- The Pest suite runs the same scenarios in-process (`tests/Feature/Simulator`) through the full middleware stack, so they stay in step with the code.
