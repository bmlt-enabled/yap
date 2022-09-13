<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST['SmsSid'] = "abc123";
    $_REQUEST['To'] = "+12125551212";
    $_REQUEST['From'] = "+19737771313";
});

test('initial sms gateway default', function () {
    $_REQUEST['Body'] = '27592';
    $response = $this->get('/sms-gateway.php');
    $response
        ->assertStatus(200)
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=&amp;Longitude=</Redirect>',
            '</Response>',
    ], false);
});

test('initial sms gateway talk option', function () {
    $_REQUEST['Body'] = 'talk 27592';
    $response = $this->get('/sms-gateway.php');
    $response
        ->assertStatus(200)
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-sms.php?OriginalCallerId=+19737771313&amp;To=+12125551212&amp;Latitude=&amp;Longitude=</Redirect>',
            '</Response>',
        ], false);
});

test('initial sms gateway with a blackholed number', function () {
    $_REQUEST['Body'] = '27592';
    $_SESSION['override_sms_blackhole'] = "+19737771313";
    $response = $this->get('/sms-gateway.php');
    $response
        ->assertStatus(200)
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
        ], false);
});
