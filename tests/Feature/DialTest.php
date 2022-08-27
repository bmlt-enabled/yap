<?php

beforeEach(function() {
    $_SERVER['REQUEST_URI'] = "/";
    putenv("ENVIRONMENT=test");
});

it('initial call-in default', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});
