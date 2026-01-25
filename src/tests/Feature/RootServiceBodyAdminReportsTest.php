<?php

use App\Constants\AuthMechanism;
use App\Constants\EventId;
use App\Models\RecordEvent;
use App\Models\User;
use App\Services\HttpService;
use App\Services\RootServerService;
use App\Structures\RecordType;
use Laravel\Sanctum\Sanctum;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

function createRootServerServiceMock($serviceBodies): RootServerService
{
    $rootServerService = mock(RootServerService::class, [app(HttpService::class)])->makePartial();

    $rootServerService->shouldReceive("getServiceBodies")
        ->withNoArgs()
        ->andReturn($serviceBodies);

    $rootServerService->shouldReceive("getServiceBodiesForUser")
        ->withAnyArgs()
        ->andReturnUsing(function ($includeGeneral) use ($serviceBodies) {
            $result = $serviceBodies;
            if ($includeGeneral) {
                $result[] = (object)["id" => "0", "name" => "All"];
            }
            return $result;
        });

    $rootServerService->shouldReceive("getServiceBodiesForUserRecursively")
        ->withAnyArgs()
        ->andReturnUsing(function ($serviceBodyId) use ($serviceBodies) {
            $ids = [intval($serviceBodyId)];
            foreach ($serviceBodies as $sb) {
                if (intval($sb->parent_id) === intval($serviceBodyId)) {
                    $ids[] = intval($sb->id);
                }
            }
            return $ids;
        });

    foreach ($serviceBodies as $sb) {
        $rootServerService->shouldReceive("getServiceBody")
            ->with($sb->id)
            ->andReturn($sb);
    }

    return $rootServerService;
}

test('root service body admin can access metrics for service_body_id=0', function () {
    // Root service body (parent_id = null)
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$rootServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1000"]);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(200);
});

test('non-root service body admin cannot access metrics for service_body_id=0', function () {
    // Child service body (has parent)
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$childServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1001"]);
    session()->put('auth_service_bodies_rights', ["1001"]);

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('yap server admin can access metrics for service_body_id=0', function () {
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$rootServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', true);

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(200);
});

test('root service body admin can access CDR for service_body_id=0', function () {
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$rootServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1000"]);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(200);
});

test('non-root service body admin cannot access CDR for service_body_id=0', function () {
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$childServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1001"]);
    session()->put('auth_service_bodies_rights', ["1001"]);

    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('root service body admin can access map metrics for service_body_id=0', function () {
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$rootServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1000"]);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $response = $this->call('GET', '/api/v1/reports/mapmetrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(200);
});

test('non-root service body admin cannot access map metrics for service_body_id=0', function () {
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$childServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1001"]);
    session()->put('auth_service_bodies_rights', ["1001"]);

    $response = $this->call('GET', '/api/v1/reports/mapmetrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('root service body admin sees meeting request logs with null service_body_id', function () {
    $rootServiceBody = (object)[
        "id" => "1000",
        "parent_id" => null,
        "name" => "Root Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$rootServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    // Create a meeting search event with service_body_id = 0 (NULL in DB)
    RecordEvent::generate(
        "meeting_search_123",
        EventId::MEETING_SEARCH,
        "2023-01-03 12:00:00",
        0, // NULL service_body_id converted to 0
        "",
        RecordType::PHONE
    );

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1000"]);
    session()->put('auth_service_bodies_rights', ["1000"]);

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(200);
    $data = $response->json();
    // Verify that the meeting search event is included in the summary
    $foundMeetingSearch = false;
    foreach ($data['summary'] as $summary) {
        if ($summary['event_id'] == EventId::MEETING_SEARCH && $summary['counts'] > 0) {
            $foundMeetingSearch = true;
            break;
        }
    }
    expect($foundMeetingSearch)->toBeTrue();
});

test('non-root service body admin does not see meeting request logs with null service_body_id', function () {
    $childServiceBody = (object)[
        "id" => "1001",
        "parent_id" => "1000",
        "name" => "Child Service Body",
    ];

    $rootServerService = createRootServerServiceMock([$childServiceBody]);
    app()->instance(RootServerService::class, $rootServerService);

    // Create a meeting search event with service_body_id = 0 (NULL in DB)
    RecordEvent::generate(
        "meeting_search_456",
        EventId::MEETING_SEARCH,
        "2023-01-03 12:00:00",
        0, // NULL service_body_id converted to 0
        "",
        RecordType::PHONE
    );

    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);
    session()->put('auth_mechanism', AuthMechanism::V2);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', ["1001"]);
    session()->put('auth_service_bodies_rights', ["1001"]);

    // Request with service_body_id=0 should fail with 403
    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => 0,
        "date_range_start" => "2023-01-01",
        "date_range_end" => "2023-01-07",
    ]);

    $response->assertStatus(403);
});
