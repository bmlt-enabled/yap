<?php

namespace Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Canned responses for the outbound HTTP the suite would otherwise make for
 * real - the BMLT root server and the Google Maps geocoder.
 *
 * Everything the app fetches goes through App\Services\HttpService, which is
 * built entirely on the Http facade, so faking at the facade layer keeps the
 * response *parsing* in RootServerService and GeocodingService under test.
 * That is deliberate: mocking those services instead would skip the parsing,
 * which is exactly the code a framework upgrade is most likely to break. It is
 * also why new tests should prefer this over Tests\RootServerMocks, which stubs
 * only 2 of RootServerService's methods and lets the rest reach the network.
 *
 * Fixtures are raw response bodies under Fixtures/http - dumb files, no DSL.
 * See Fixtures/http/README.md for where each one came from.
 */
class FakeHttp
{
    private const FIXTURE_ROOT = __DIR__ . '/Fixtures/http';

    /**
     * The BMLT account whose credentials the auth tests expect to succeed.
     */
    private const VALID_BMLT_USER = 'gnyr_admin';

    /**
     * URL glob => fixture file, relative to Fixtures/http.
     *
     * Order matters. Laravel keeps the first stub whose glob matches, so the
     * specific patterns have to come before the general ones. Two cases rely on
     * that: the BMLT meeting search and helpline search both use
     * switcher=GetSearchResults and are only told apart by their data_field_key,
     * and the per-coordinate/per-address entries have to precede their
     * catch-alls.
     */
    private const STUBS = [
        // BMLT meeting search (MeetingResultsService). The weekday comes from the
        // Timestamp the test posts, so these are stable rather than date-dependent.
        '*switcher=GetSearchResults*data_field_key=id_bigint*lat_val=42.867970*weekdays=2*'
            => 'bmlt/meeting-search-42.867970-day2.json',
        '*switcher=GetSearchResults*data_field_key=id_bigint*lat_val=42.867970*weekdays=3*'
            => 'bmlt/meeting-search-42.867970-day3.json',
        '*switcher=GetSearchResults*data_field_key=id_bigint*lat_val=42.867970*weekdays=4*'
            => 'bmlt/meeting-search-42.867970-day4.json',
        '*switcher=GetSearchResults*data_field_key=id_bigint*'
            => 'bmlt/meeting-search-0-0.json',

        // BMLT helpline routing, keyed on the coordinates each address geocodes to
        // so a search resolves to the service body its test expects.
        '*service_body_bigint*lat_val=42.8864163*' => 'bmlt/helpline-search-buffalo-ny.json',
        '*service_body_bigint*lat_val=40.6526006*' => 'bmlt/helpline-search-brooklyn-ny.json',
        '*service_body_bigint*lat_val=42.8690271*' => 'bmlt/helpline-search-geneva-ny.json',
        '*service_body_bigint*lat_val=34.2474330*' => 'bmlt/helpline-search-brooklyn-nc.json',
        '*service_body_bigint*lat_val=40.912252*' => 'bmlt/helpline-search-geneva-override.json',
        '*service_body_bigint*lat_val=42.8361156*' => 'bmlt/helpline-search-14456.json',
        '*service_body_bigint*' => 'bmlt/helpline-search-empty.json',

        // BMLT reference data.
        '*switcher=GetServiceBodies*' => 'bmlt/service-bodies.json',
        '*switcher=GetFormats*' => 'bmlt/formats.json',
        '*switcher=GetServerInfo*' => 'bmlt/server-info.json',

        // BMLT semantic admin (the V1 auth path).
        '*/server_admin/json.php*get_permissions*' => 'bmlt/permissions.json',
        '*/server_admin/json.php*get_user_info*' => 'bmlt/user-info.json',

        // Google geocoding, keyed on the urlencoded address.
        '*/geocode/json*address=Raleigh%2C+NC*' => 'google/geocode-raleigh-nc.json',
        '*/geocode/json*address=Buffalo%2C+NY*' => 'google/geocode-buffalo-ny.json',
        '*/geocode/json*address=Brooklyn%2C+NY*' => 'google/geocode-brooklyn-ny.json',
        '*/geocode/json*address=Brooklyn%2C+NC*' => 'google/geocode-brooklyn-nc.json',
        '*/geocode/json*address=Geneva%2C+NY*' => 'google/geocode-geneva-ny.json',
        '*/geocode/json*address=14456*' => 'google/geocode-14456.json',
        '*/geocode/json*address=27592*' => 'google/geocode-27592.json',
        '*/geocode/json*address=91409*' => 'google/geocode-91409.json',
        '*/geocode/json*address=98382*' => 'google/geocode-98382.json',
        // Anything else is an address Google cannot place.
        '*/geocode/json*' => 'google/geocode-no-results.json',

        // UpgradeService pings the timezone API to validate the key.
        '*/maps/api/timezone/json*' => 'google/timezone.json',
    ];

    /**
     * Install the canned responses for the current test.
     *
     * Pass $overrides to change the response for a specific call. Because
     * Laravel keeps the first matching stub, overrides are registered ahead of
     * the defaults:
     *
     *     FakeHttp::install(['*switcher=GetServerInfo*' => Http::response('[]', 500)]);
     */
    public static function install(array $overrides = []): void
    {
        Http::fake($overrides + self::stubs());
    }

    /**
     * @return array<string, callable>
     */
    private static function stubs(): array
    {
        // The BMLT login endpoint answers "OK" or an error page depending on the
        // credentials posted, so it needs the request to decide - a URL glob
        // cannot tell the two apart.
        $stubs = [
            '*/local_server/server_admin/xml.php*' => function (Request $request) {
                $isValidUser = ($request->data()['c_comdef_admin_login'] ?? null) === self::VALID_BMLT_USER;

                return Http::response(
                    $isValidUser ? self::fixture('bmlt/auth-ok.txt') : 'NOT AUTHORIZED'
                );
            },
        ];

        foreach (self::STUBS as $glob => $fixture) {
            $stubs[$glob] = Http::response(self::fixture($fixture));
        }

        return $stubs;
    }

    /**
     * Read a fixture body, failing loudly rather than quietly stubbing an empty
     * response when the file is missing.
     */
    public static function fixture(string $path): string
    {
        $absolute = self::FIXTURE_ROOT . '/' . $path;

        if (!is_file($absolute)) {
            throw new RuntimeException("Missing HTTP fixture: {$path}");
        }

        return file_get_contents($absolute);
    }
}
