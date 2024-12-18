<?php

use App\Constants\AuthMechanism;
use App\Models\User;
use App\Services\SettingsService;
use Laravel\Sanctum\Sanctum;

test('check language selections when not set', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>[""]]);
});

test('check language selections when set', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $settingsService = new SettingsService();
    $settingsService->set('language_selections', "en-US,fr-CA");
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>["en-US", "fr-CA"]]);
});

test('check language selections tagged when set', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $settingsService = new SettingsService();
    $settingsService->set('language_selections_tagging', "en-US,fr-CA");
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>["en-US", "fr-CA"]]);
});

test('session overridden settings', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    session()->put('override_title', "blah");

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonFragment(["key"=>"title", "docs"=>"", "value"=>"blah", "default"=>"", "source"=>"Session Override"]);
});

test('querystring overridden settings', function () {
    Sanctum::actingAs(User::factory()->create());
    ;

    $response = $this->call('GET', '/api/v1/settings', ['title'=>'son']);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonFragment(["key"=>"title", "docs"=>"", "value"=>"son", "default"=>"", "source"=>"Transaction Override"]);
});

test('querystring based session key', function () {
    $title = "something something something, dark side";
    session()->start();
    Sanctum::actingAs(User::factory()->create());
    ;

    $sessionId = session()->getId();
    $this->assertNotEmpty($sessionId);

    session()->put('override_title', $title);

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonFragment(["key"=>"title", "docs"=>"", "value"=>$title, "default"=>"", "source"=>"Session Override"]);

    $ysk = getSessionCookieValue($response);
    $this->assertNotEmpty($ysk, "laravel_session cookie value is empty");

    session()->flush();

    Sanctum::actingAs(User::factory()->create());
    ;
    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonFragment(["key"=>"title", "docs"=>"", "value"=>"Test Helpline", "default"=>"", "source"=>"Factory Default"]);

    $response = $this->call('GET', '/api/v1/settings', ['ysk'=>$ysk]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonFragment(["key"=>"title", "docs"=>"", "value"=>$title, "default"=>"", "source"=>"Session Override"]);
});
