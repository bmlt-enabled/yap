<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Models\User;
use App\Services\RootServerService;
use App\Structures\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\RootServerMocks;

beforeEach(function () {
    $this->id = "200";
    $this->serviceBodyId = 1006;
    $this->rootServerMocks = new RootServerMocks();
});

test('get config from endpoint', function () {
    $config = new Settings();
    $config->title = "welcome to blah";
    Sanctum::actingAs(User::factory()->create());
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $config
    );

    $response = $this->call('GET', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ]);
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>0,
            "data"=>[$config->toArray()]]);
});

test('get config from endpoint using BMLT no auth', function () {
    $config = new Settings();
    $config->title = "welcome to blah";

    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $config
    );

    $response = $this->call('GET', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ]);
    $response->assertStatus(302)
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertHeader("Location", 'http://localhost/admin');
});

test('get config for invalid service body', function () {
    $config = new Settings();
    Sanctum::actingAs(User::factory()->create());
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    ConfigData::createServiceBodyConfiguration(
        99,
        $config
    );

    $this->call('GET', '/api/v1/config', [
        "serviceBodyId" => 99,
    ])->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);
});

test('save config', function () {
    Sanctum::actingAs(User::factory()->create());
    $serviceBodyConfigData = new Settings();
    $serviceBodyConfigData->title = "welcome to blah";
    $response = $this->call('POST', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>0,
            "data"=>[$serviceBodyConfigData->toArray()]]);

    $response = $this->call('GET', '/', ['service_body_id'=>$this->serviceBodyId]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">welcome to blah</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('save config with twilio creds', function () {
    Sanctum::actingAs(User::factory()->create());
    $serviceBodyConfigData = new Settings();
    $serviceBodyConfigData->twilio_account_sid = "abc";
    $serviceBodyConfigData->mobile_check = true;
    $response = $this->call('POST', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>0,
            "data"=>[$serviceBodyConfigData->toArray()]]);

    $this->call('GET', '/ping', ['service_body_id'=>$this->serviceBodyId]);
    $this->assertNull(session()->get("override_twilio_account_sid"));
    $this->assertTrue(session()->get("override_mobile_check"));
});

test('update config', function () {
    Sanctum::actingAs(User::factory()->create());
    $serviceBodyConfigData = new Settings();
    $serviceBodyConfigData->title = "welcome to blah";
    $response = $this->call('POST', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>0,
            "data"=>[$serviceBodyConfigData->toArray()]]);

    $serviceBodyConfigData->title = "welcome to blih";
    $response = $this->call('POST', '/api/v1/config', [
        "serviceBodyId" => $this->serviceBodyId,
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>0,
            "data"=>[$serviceBodyConfigData->toArray()]]);

    $response = $this->call('GET', '/', ['service_body_id'=>$this->serviceBodyId]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">welcome to blih</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});


test('get config no auth', function () {
    $response = $this->call('GET', '/api/v1/config', [
        "serviceBodyId" => 0,
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
