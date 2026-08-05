# HTTP fixtures

Raw response bodies for the outbound calls the test suite would otherwise make
for real. `tests/FakeHttp.php` maps a URL glob to each file; `tests/Pest.php`
calls `Http::preventStrayRequests()` so anything not stubbed fails loudly
instead of reaching the network.

## `bmlt/` — recorded live

Recorded from `https://latest.aws.bmlt.app/main_server`, the server
`config.test.php` points at, so they snapshot that server's real responses.

To refresh one, request the same URL and overwrite the file:

```bash
ROOT="https://latest.aws.bmlt.app/main_server"

curl "$ROOT/client_interface/json/?switcher=GetServiceBodies" -o bmlt/service-bodies.json
curl "$ROOT/client_interface/json/?switcher=GetServerInfo"    -o bmlt/server-info.json
curl "$ROOT/client_interface/json/?switcher=GetFormats"       -o bmlt/formats.json
```

`meeting-search-*.json` and `helpline-search-*.json` are per-coordinate, because
`FakeHttp` keys them on the `lat_val` in the request so each search resolves to
the service body its test expects. The suffix is the coordinate (and, for the
meeting search, the weekday the test's `Timestamp` produces).

```bash
DFK="id_bigint,meeting_name,weekday_tinyint,start_time,location_text,location_info,location_municipality,location_province,location_street,longitude,latitude,distance_in_miles,distance_in_km,formats,virtual_meeting_link,phone_meeting_number,virtual_meeting_additional_info"

# meeting search - MeetingSearchTest posts Timestamp 2024-02-19, a Monday, so weekdays=2
curl "$ROOT/client_interface/json/?switcher=GetSearchResults&get_used_formats&data_field_key=$DFK&sort_results_by_distance=1&long_val=-76.985573&lat_val=42.867970&geo_width=-50&weekdays=2" \
  -o bmlt/meeting-search-42.867970-day2.json

# helpline search - one per coordinate, geo_width is helpline_search_radius (30)
curl "$ROOT/client_interface/json/?switcher=GetSearchResults&data_field_key=longitude,latitude,service_body_bigint&sort_results_by_distance=1&lat_val=42.8864163&long_val=-78.8781493&geo_width=30" \
  -o bmlt/helpline-search-buffalo-ny.json
```

`auth-ok.txt` is not a recording. `RootServerService::authenticate()` only checks
that the body matches `/^OK$/`, so the fixture is the literal string; faking it
avoids POSTing real credentials at a live server on every run. `FakeHttp` returns
it only for the account the auth tests expect to succeed, and an error body for
any other, so the failed-login cases still exercise their branch.

`permissions.json` and `user-info.json` are hand-authored too - they are behind
the semantic-admin session cookie, so they cannot be fetched with a plain curl.
`permissions.json` grants service body `1` (Greater New York Region), which is
the body the `gnyr_admin` account the auth tests use administers, and whose id
exists in `service-bodies.json`. Note the `id` must be a JSON **number**:
`RootServerService::getServiceBodiesRights()` compares it with `===` against
`intval()`, so a string silently matches nothing.

## `google/` — hand-authored

These were **not** recorded, because no Google Maps API key is available to this
repository — removing that key from CI is the point of the change these fixtures
support.

They are safe to hand-author because `GeocodingService::getCoordinatesForAddress()`
reads only four fields:

- `status`
- `results[0].formatted_address`
- `results[0].geometry.location.lat`
- `results[0].geometry.location.lng`

The values encode current behaviour rather than inventing it:

| Fixture | Coordinates from |
|---|---|
| `geocode-raleigh-nc.json`, `geocode-27592.json`, `geocode-98382.json` | the assertions already in `AddressLookupTest` |
| `geocode-14456.json` | the coordinates `HelplineSearchTest` itself constructs |
| `geocode-buffalo-ny.json`, `geocode-brooklyn-ny.json`, `geocode-brooklyn-nc.json`, `geocode-geneva-ny.json` | OpenStreetMap Nominatim, then verified against BMLT: each resolves to the service body its test expects (Buffalo → 1060, Geneva → 1053, Brooklyn NC → no coverage) |
| `geocode-91409.json`, `timezone.json` | the address and coordinates `UpgradeService` pings to validate the key; only `status` is read |
| `geocode-no-results.json` | the `ZERO_RESULTS` shape, for addresses Google cannot place |

If a real key is ever available, these can be replaced with true recordings:

```bash
curl "https://maps.googleapis.com/maps/api/geocode/json?key=$GOOGLE_MAPS_API_KEY&address=Raleigh%2C+NC&components=country%3Aus" \
  -o google/geocode-raleigh-nc.json
```

## Transports `preventStrayRequests()` cannot see

`Http::preventStrayRequests()` only guards the `Http` facade. That covers all of
`App\Services\HttpService` (and so every BMLT and Google call), but two
dependencies build their own clients and are invisible to it. Both are stubbed
by hand instead, and a test that reaches either will make a real request without
failing — so if you add a test that touches one, stub it explicitly:

| Transport | Reached via | Stub with |
|---|---|---|
| Twilio SDK (`Twilio\Rest\Client`) | `TwilioService` | `setupTwilioService()`, or `useUnauthorizedTwilioClient()` to assert on a 401 |
| `bmlt/fetch-meditation` (its own Guzzle client) | `ReadingService` | `Mockery::mock(ReadingService::class)` — see `FetchJFTTest` |

There is also a raw cURL call to api.github.com in
`UpgradeAdvisorController::getVersionInfo()`. No test reaches it today because
the only method that calls it, `version()`, has no route — `/api/v1/version` is
a closure in `routes/api.php`. Routing it would open a hole here.

## Adding a fixture

1. Add the file here.
2. Add a `'*url*glob*' => 'path/to/fixture.json'` entry to `FakeHttp::STUBS`.
   Order matters — Laravel keeps the **first** matching stub, so put specific
   patterns above general ones.

To let a test reach the network while working out what to record, set
`YAP_ALLOW_STRAY_HTTP=1`. It is deliberately not set anywhere in CI.
