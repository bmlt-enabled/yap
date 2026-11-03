<?php

use App\Services\RootServerService;
use App\Services\SettingsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\FakeHttp;

// The BMLT root server setting is typed by operators, with or without a
// trailing slash. Both forms must produce the same URLs (#1659).
const ROOT_SERVER_1659 = 'https://latest.aws.bmlt.app/main_server';
const BMLT_SESSION_COOKIE_1659 = 'PHPSESSID=bmlt-session-1659';

function useRootServer1659(string $url): void
{
    $settings = new SettingsService();
    $settings->set('bmlt_root_server', $url);
    app()->instance(SettingsService::class, $settings);
}

function sentUrls1659(): array
{
    return Http::recorded()->map(fn ($pair) => $pair[0]->url())->all();
}

beforeEach(function () {
    FakeHttp::install([
        // Login hands back a BMLT session cookie, so the test can check that
        // logout sends it back (otherwise BMLT has no session to end).
        '*/local_server/server_admin/xml.php*' => function (Request $request) {
            if (($request->data()['admin_action'] ?? null) === 'login') {
                $ok = ($request->data()['c_comdef_admin_login'] ?? null) === 'gnyr_admin';
                return Http::response(
                    $ok ? 'OK' : 'NOT AUTHORIZED',
                    200,
                    $ok ? ['Set-Cookie' => BMLT_SESSION_COOKIE_1659 . '; path=/'] : []
                );
            }
            return Http::response('OK');
        },
        '*data_field_key=root_server_uri*' => Http::response(json_encode([
            ['root_server_uri' => ROOT_SERVER_1659],
        ])),
    ]);
});

dataset('root server settings', [
    'no trailing slash' => ROOT_SERVER_1659,
    'trailing slash' => ROOT_SERVER_1659 . '/',
    'surrounding whitespace' => ' ' . ROOT_SERVER_1659 . '/ ',
]);

test('root server getters strip the trailing slash', function (string $setting) {
    useRootServer1659($setting);
    $settings = app(SettingsService::class);

    expect($settings->getAdminBMLTRootServer())->toBe(ROOT_SERVER_1659)
        ->and($settings->getBMLTRootServer())->toBe(ROOT_SERVER_1659);

    $settings->set('tomato_url', $setting);
    expect($settings->getTomatoUrl())->toBe(ROOT_SERVER_1659);
})->with('root server settings');

test('BMLT user logout ends the BMLT session', function (string $setting) {
    useRootServer1659($setting);

    $token = $this->post('/api/v1/login', ['username' => 'gnyr_admin', 'password' => 'CoreysGoryStory'])
        ->assertStatus(200)
        ->json('token');

    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/logout')
        ->assertStatus(200);

    Http::assertSent(fn (Request $request) =>
        $request->method() === 'GET'
        && $request->url() === ROOT_SERVER_1659 . '/local_server/server_admin/xml.php?admin_action=logout'
        && str_contains($request->header('Cookie')[0] ?? '', BMLT_SESSION_COOKIE_1659));

    foreach (sentUrls1659() as $url) {
        expect(substr($url, strlen('https://')))->not->toContain('//');
    }
})->with('root server settings');

test('database user logout does not call the root server', function () {
    DB::statement(
        "INSERT INTO users (name, username, password, permissions, is_admin) VALUES ('admin', ?, SHA2(?, 256), 0, 1)",
        ['admin1', 'admin1']
    );
    $token = $this->post('/api/v1/login', ['username' => 'admin1', 'password' => 'admin1'])
        ->assertStatus(200)
        ->json('token');

    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/logout')
        ->assertStatus(200);

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'admin_action=logout'));
});

test('helpline routing ownership check ignores a trailing slash', function (string $setting) {
    useRootServer1659($setting);

    expect(app(RootServerService::class)->isBMLTServerOwned(42.8864163, -78.8783689))->toBeTrue();
})->with('root server settings');
