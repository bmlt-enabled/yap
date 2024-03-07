<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('invalid entry', function ($method) {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "7";
    $response = $this->call($method, '/gender-routing-response.php?SearchType=1&Digits=7');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect method="GET">gender-routing.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('selected option', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/gender-routing-response.php?Digits=1&SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?SearchType=1</Redirect>',
            '</Response>'
        ], false);
});

test('initial gender selection', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/gender-routing.php?Digits=1&SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="10" speechTimeout="auto" action="gender-routing-response.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">',
            'press one to talk to a man, press two to talk to a woman</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial gender selection with no preference option enabled', function () {
    $_REQUEST['SearchType'] = "1";
    $_SESSION['override_gender_no_preference'] = true;
    $response = $this->call('GET', '/gender-routing.php?SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="10" speechTimeout="auto" action="gender-routing-response.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">',
            'press one to talk to a man, press two to talk to a woman, press three to speak to either</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});
