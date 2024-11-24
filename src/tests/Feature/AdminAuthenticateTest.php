<?php

use App\Constants\AuthMechanism;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Models\User;
use App\Structures\ServiceBodyCallHandling;
use App\Services\AuthenticationService;
use App\Repositories\AuthenticationRepository;
use App\Services\RootServerService;
use App\Services\SessionService;
use App\Services\SettingsService;
use App\Services\HttpService;
use App\Services\ConfigService;
use App\Repositories\ConfigRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('login to authenticate with a BMLT user and a user with no rights', function () {
    $this->post(
        '/admin/login',
        ["username"=>"yap","password"=>"CoreysGoryStory"]
    )->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/auth/invalid')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a BMLT user and a user with rights', function () {
    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );

    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a BMLT user get username', function () {
    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );

    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");

    $httpService = new HttpService();
    $settingsService = new SettingsService();
    $authenticationRepository = new AuthenticationRepository($settingsService, $httpService);
    $rootServerService = new RootServerService($httpService);
    $configRepository = new ConfigRepository($rootServerService);
    $configService = new ConfigService($configRepository, $rootServerService);

    $sessionService = new SessionService($configService, $rootServerService);
    $authenticationService = new AuthenticationService($authenticationRepository, $rootServerService, $sessionService);
    $username = $authenticationService->username();
    $this->assertEquals("gnyr_admin", $username);
});

test('login to authenticate with a BMLT user and logout and try to get username which should be null', function () {
    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");

        $httpService = new HttpService();
        $settingsService = new SettingsService();
        $authenticationRepository = new AuthenticationRepository($settingsService, $httpService);
        $rootServerService = new RootServerService($httpService);
        $configRepository = new ConfigRepository($rootServerService);
        $configService = new ConfigService($configRepository, $rootServerService);
    
        $sessionService = new SessionService($configService, $rootServerService);
        $authenticationService = new AuthenticationService($authenticationRepository, $rootServerService, $sessionService);
        $username = $authenticationService->username();
        $this->assertEquals("gnyr_admin", $username);

        $response = $this->get('/admin/auth/logout');
        $response
            ->assertStatus(302)
            ->assertHeader("Location", 'http://localhost/admin')
            ->assertHeader("Content-Type", "text/html; charset=utf-8");
    
        $username = $authenticationService->username();
        $this->assertNull($username);
});

test('login to authenticate with a yap admin user', function () {
    User::create([
        "name"=>"admin",
        "username"=>"admin",
        "password"=>hash("sha256", "admin"),
        "permissions"=>0,
        "is_admin"=>1
    ]);

    $response = $this->post(
        '/admin/login',
        ["username"=>"admin","password"=>"admin"]
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a yap user', function () {
    User::create([
        "name"=>"user",
        "username"=>"user",
        "password"=>hash("sha256", "user"),
        "permissions"=>0,
        "is_admin"=>0,
        "service_bodies"=>"1006,1005"
    ]);

    $response = $this->post(
        '/admin/login',
        ["username"=>"user","password"=>"user"]
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('logout with a yap admin user', function () {
    User::create([
        "name"=>"admin",
        "username"=>"admin",
        "password"=>hash("sha256", "admin"),
        "permissions"=>0,
        "is_admin"=>1
    ]);

    $response = $this->get(
        '/admin/auth/logout'
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('logout with a BMLT user', function () {
    session()->put('auth_mechanism', AuthMechanism::V1);
    session()->put('bmlt_auth_session', ["test_cookie=blah"]);
    $response = $this->get(
        '/admin/auth/logout'
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

// TODO: this test should be removed after we load data solely with React
test('get service body call handling (legacy)', function () {
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', true);
    session()->put('username', "admin");

    $serviceBodyCallHandling = new ServiceBodyCallHandling();
    $serviceBodyCallHandling->volunteer_routing_enabled = true;
    $serviceBodyCallHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;

    ConfigData::createServiceBodyCallHandling(1, $serviceBodyCallHandling);

    $response = $this->get('/admin/volunteers');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/html; charset=UTF-8");
});
