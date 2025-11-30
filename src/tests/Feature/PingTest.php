<?php
test('ping with php extension', function ($method) {
    $response = $this->call($method, '/ping.php', [
        //'ysk'=>'123'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=utf-8")
        ->assertSeeText("PONG", false);
})->with(['GET', 'POST']);

test('ping without php extension', function ($method) {
    $response = $this->call($method, '/ping');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=utf-8")
        ->assertSeeText("PONG", false);
})->with(['GET', 'POST']);
