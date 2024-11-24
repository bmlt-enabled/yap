<?php

use App\Services\SettingsService;

test('get the JFT in English', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("Just for Today", false);
})->with(['GET', 'POST'], ['en-US', 'en-AU']);

test('get the JFT in Portuguese', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("Só por hoje", false);
})->with(['GET', 'POST'], ['pt-BR', 'pt-PT']);

test('get the JFT in Spanish', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.", false);
})->with(['GET', 'POST'], ['es-US', 'es-ES']);

test('get the JFT in French', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("NA World Services, Inc. All Rights Reserved", false);
})->with(['GET', 'POST'], ['fr-FR', 'fr-CA']);
