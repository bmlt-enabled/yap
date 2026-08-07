<?php

use Illuminate\Support\Facades\Route;

test('no route references a removed middleware alias', function () {
    $removedAliases = ['authForAdminPortal', 'auth.basic', 'guest'];

    foreach (Route::getRoutes() as $route) {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            $alias = explode(':', $middleware, 2)[0];
            expect($alias)->not->toBeIn(
                $removedAliases,
                sprintf(
                    'Route %s %s references removed middleware alias %s',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $alias
                )
            );
        }
    }
});

test('every route middleware alias is registered', function () {
    $router = app('router');
    $registered = array_merge(
        array_keys($router->getMiddleware()),
        array_keys($router->getMiddlewareGroups())
    );

    foreach (Route::getRoutes() as $route) {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware) || class_exists($middleware)) {
                continue;
            }

            $alias = explode(':', $middleware, 2)[0];
            expect($alias)->toBeIn(
                $registered,
                sprintf(
                    'Route %s %s references unregistered middleware alias %s',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $alias
                )
            );
        }
    }
});

test('security: unauthenticated burst against auth:sanctum route is throttled', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    $this->getJson('/api/v1/users')->assertStatus(429);
});
