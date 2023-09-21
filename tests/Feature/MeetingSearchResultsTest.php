<?php

use function PHPUnit\Framework\assertMatchesRegularExpression;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
    include __DIR__ . '/../../lang/en-US.php';
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('meeting seating results', function () {
    $response = $this->get('/msr/35.560471/-78.670792');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/html; charset=UTF-8")
        ->assertSeeInOrder([
            '<html lang="en">',
            '<head>',
            '<title>Meetings</title>',
    ], false);
});

test('meeting seating results bad latitude and longitude', function () {
    $response = $this->get('/msr/bad/bad');
    $response->assertStatus(404);
});

test('meeting seating results bad latitude', function () {
    $response = $this->get('/msr/bad/-78.670792');
    $response->assertStatus(404);
});

test('meeting seating results bad longitude', function () {
    $response = $this->get('/msr/35.560471/bad');
    $response->assertStatus(404);
});
