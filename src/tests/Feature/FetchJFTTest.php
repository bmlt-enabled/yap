<?php

use App\Services\HttpService;
use App\Services\SettingsService;
use Tests\Stubs;

test('get the JFT in English', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $httpService = mock('App\Services\HttpService')->makePartial();
    $httpService->shouldReceive('get')
        ->withArgs(["https://www.jftna.org/jft/", 3600])
        ->once()
        ->andReturn(Stubs::jftEn());
    app()->instance(HttpService::class, $httpService);

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

    $httpService = mock('App\Services\HttpService')->makePartial();
    $httpService->shouldReceive('get')
        ->withArgs(["http://www.na.org.br/meditacao", 3600])
        ->once()
        ->andReturn(Stubs::jftPt());
    app()->instance(HttpService::class, $httpService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("Todos os direitos reservados à", false);
})->with(['GET', 'POST'], ['pt-BR', 'pt-PT']);

test('get the JFT in Spanish', function ($method, $language) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", $language);
    app()->instance(SettingsService::class, $settingsService);

    $httpService = mock('App\Services\HttpService')->makePartial();
    $httpService->shouldReceive('get')
        ->withArgs(["https://forozonalatino.org/sxh", 3600])
        ->once()
        ->andReturn(Stubs::jftEs());
    app()->instance(HttpService::class, $httpService);

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

    $httpService = mock('App\Services\HttpService')->makePartial();
    $httpService->shouldReceive('get')
        ->withArgs(["https://jpa.narcotiquesanonymes.org", 3600])
        ->once()
        ->andReturn(Stubs::jftFr());
    app()->instance(HttpService::class, $httpService);

    $response = $this->call($method, '/fetch-jft.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("NA World Services, Inc. All Rights Reserved", false);
})->with(['GET', 'POST'], ['fr-FR', 'fr-CA']);
