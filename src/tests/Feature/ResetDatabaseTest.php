<?php

/**
 * POST /api/resetDatabase used to run `migrate:fresh --seed`. It was registered
 * unconditionally, outside the auth:sanctum group, unthrottled, and guarded only
 * by an exact match on config('app.env') === 'production' -- so any install whose
 * APP_ENV was 'staging', 'local' or unset exposed unauthenticated total data
 * destruction to the internet.
 *
 * The route was removed (issue #1575); the e2e suite now resets via artisan in
 * src/e2e/fixtures/auth.js. This test exists so the route is not reintroduced.
 */
test('database reset endpoint does not exist', function ($method) {
    $response = $this->json($method, '/api/resetDatabase');

    $response->assertStatus(404);
})->with(['POST', 'GET']);
