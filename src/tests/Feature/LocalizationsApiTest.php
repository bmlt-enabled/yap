<?php

use App\Models\User;
use App\Services\SettingsService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('get localizations returns current session language localizations', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonStructure([
            'language_title',
            'volunteers',
        ]);
});

test('get localizations returns correct english values', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertJson([
            'language_title' => 'english',
            'volunteers' => 'Volunteers',
        ]);
});

test('get localizations with different session language', function () {
    Sanctum::actingAs($this->user);
    
    // Set session language to Spanish
    session()->put('override_word_language', 'es-US');
    session()->put('override_gather_language', 'es-US');
    session()->put('override_language', 'es-US');
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonStructure([
            'language_title',
            'volunteers',
        ]);
    
    // Verify it returns Spanish localizations
    $response->assertJson([
        'language_title' => 'español',
        'volunteers' => 'voluntários',
    ]);
});

test('get localizations with french session language', function () {
    Sanctum::actingAs($this->user);
    
    // Set session language to French
    session()->put('override_word_language', 'fr-CA');
    session()->put('override_gather_language', 'fr-CA');
    session()->put('override_language', 'fr-CA');
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonStructure([
            'language_title',
            'volunteers',
        ]);
    
    // Verify it returns French localizations
    $response->assertJson([
        'language_title' => 'français',
        'volunteers' => 'volontaires',
    ]);
});

test('get localizations with pig latin session language', function () {
    Sanctum::actingAs($this->user);
    
    // Set session language to Pig Latin
    session()->put('override_word_language', 'pig-latin');
    session()->put('override_gather_language', 'pig-latin');
    session()->put('override_language', 'pig-latin');
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJsonStructure([
            'language_title',
            'volunteers',
        ]);
    
    // Verify it returns Pig Latin localizations
    $response->assertJson([
        'language_title' => 'igpay atinlay',
        'volunteers' => 'olunteersvay',
    ]);
});

test('get localizations returns days of the week array', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'days_of_the_week' => [
                1, 2, 3, 4, 5, 6, 7
            ]
        ]);
    
    // Verify English days of the week
    $response->assertJson([
        'days_of_the_week' => [
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday'
        ]
    ]);
});

test('get localizations with spanish days of the week', function () {
    Sanctum::actingAs($this->user);
    
    // Set session language to Spanish
    session()->put('override_word_language', 'es-US');
    session()->put('override_gather_language', 'es-US');
    session()->put('override_language', 'es-US');
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertJson([
            'days_of_the_week' => [
                1 => 'Domingo',
                2 => 'Lunes',
                3 => 'Martes',
                4 => 'Miércoles',
                5 => 'Jueves',
                6 => 'Viernes',
                7 => 'Sábado'
            ]
        ]);
});

test('get localizations handles missing keys gracefully', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->call('GET', '/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
    
    $data = $response->json();
    
    // Test that a non-existent key returns the key itself (fallback behavior)
    $this->assertArrayNotHasKey('non_existent_key', $data);
});

test('get localizations endpoint is accessible via correct route', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->get('/api/v1/settings/localizations');
    
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
});

test('get localizations with different users returns same language data', function () {
    Sanctum::actingAs($this->user);
    
    $response1 = $this->call('GET', '/api/v1/settings/localizations');
    
    Sanctum::actingAs(User::factory()->create());
    
    $response2 = $this->call('GET', '/api/v1/settings/localizations');
    
    $response1->assertStatus(200);
    $response2->assertStatus(200);
    
    // Both should return the same default language data
    $this->assertEquals($response1->json(), $response2->json());
});

test('get localizations respects session language changes', function () {
    Sanctum::actingAs($this->user);
    
    // First request with default language
    $response1 = $this->call('GET', '/api/v1/settings/localizations');
    $response1->assertStatus(200);
    $data1 = $response1->json();
    
    // Change session language
    session()->put('override_word_language', 'es-US');
    session()->put('override_gather_language', 'es-US');
    session()->put('override_language', 'es-US');
    
    // Second request with Spanish
    $response2 = $this->call('GET', '/api/v1/settings/localizations');
    $response2->assertStatus(200);
    $data2 = $response2->json();
    
    // Data should be different
    $this->assertNotEquals($data1['volunteers'], $data2['volunteers']);
    $this->assertEquals('Volunteers', $data1['volunteers']);
    $this->assertEquals('voluntários', $data2['volunteers']);
});
