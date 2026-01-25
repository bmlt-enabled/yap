<?php

use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\HttpService;
use App\Services\RootServerService;
use Laravel\Sanctum\Sanctum;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('isRootServiceBodyAdmin returns true for top level admin', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeTrue();
});

test('isRootServiceBodyAdmin returns true for user with root service body', function () {
    // Root service body (parent_id = null)
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1000")
        ->andReturn($rootServiceBody);

    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeTrue();
});

test('isRootServiceBodyAdmin returns true for user with root service body (parent_id = 0)', function () {
    // Root service body (parent_id = 0)
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => 0,
        "name" => "Root Service Body",
    ];

    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1000")
        ->andReturn($rootServiceBody);

    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeTrue();
});

test('isRootServiceBodyAdmin returns true for user with root service body (parent_id = "0")', function () {
    // Root service body (parent_id = "0" as string)
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => "0",
        "name" => "Root Service Body",
    ];

    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1000")
        ->andReturn($rootServiceBody);

    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeTrue();
});

test('isRootServiceBodyAdmin returns false for user with only child service body', function () {
    // Child service body (has a parent)
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1001")
        ->andReturn($childServiceBody);

    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies_rights', ["1001"]);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeFalse();
});

test('isRootServiceBodyAdmin returns false for user with no service bodies', function () {
    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies_rights', []);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeFalse();
});

test('isRootServiceBodyAdmin returns false when service body rights is null', function () {
    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    session()->forget('auth_service_bodies_rights');

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeFalse();
});

test('isRootServiceBodyAdmin returns true when user has mixed service bodies including root', function () {
    // Root service body
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    // Child service body
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1001")
        ->andReturn($childServiceBody);
    $rootServerService->shouldReceive("getServiceBody")
        ->with("1000")
        ->andReturn($rootServiceBody);

    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_is_admin', false);
    // User has access to both a child service body and a root service body
    session()->put('auth_service_bodies_rights', ["1001", "1000"]);

    $authorizationService = app(AuthorizationService::class);

    expect($authorizationService->isRootServiceBodyAdmin())->toBeTrue();
});
