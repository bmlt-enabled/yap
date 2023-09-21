<?php

use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('get the JFT in English', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeText("Just for Today", false);
})->with(['GET', 'POST'], ['en-US', 'en-AU']);

test('get the JFT in Portuguese', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeText("Todos os direitos reservados à", false);
})->with(['GET', 'POST'], ['pt-BR', 'pt-PT']);

test('get the JFT in Spanish', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeText("Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.", false);
})->with(['GET', 'POST'], ['es-US', 'es-ES']);

test('get the JFT in French', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeText("Juste pour aujourd’hui", false);
})->with(['GET', 'POST'], ['fr-FR', 'fr-CA']);
