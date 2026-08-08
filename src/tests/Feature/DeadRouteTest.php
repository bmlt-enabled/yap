<?php

use Illuminate\Support\Facades\Route;

test('dead /info route is not registered', function () {
    $infoRoutes = collect(Route::getRoutes())->filter(function ($route) {
        return preg_match('#(^|/)info(\.php)?$#', $route->uri());
    });

    expect($infoRoutes)->toBeEmpty();
});

test('request to /info is not handled by call flow', function () {
    $this->get('/info')->assertStatus(404);
});
