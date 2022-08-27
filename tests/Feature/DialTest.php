<?php

beforeEach(function() {
    $_SERVER['REQUEST_URI'] = "/";
});

it('initial call-in default', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});
