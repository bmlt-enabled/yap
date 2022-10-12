<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
});

test('language selections with configurations', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->get('/lng-selector.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"></Pause>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="index.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'for english press one            </Say>',
            '<Say voice="alice" language="es-US">',
            'para español presione dos            </Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('language selections without proper configuration', function () {
    $_SESSION['override_language_selections'] = null;
    $response = $this->get('/lng-selector.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Say>language gateway options are not set, please refer to the documentation to utilize this feature.</Say><Hangup/></Response>'
        ], false);
});

test('language selections with configurations and longer initial pause', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $_SESSION['override_initial_pause'] = 5;
    $response = $this->get('/lng-selector.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="5"></Pause>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="index.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'for english press one            </Say>',
            '<Say voice="alice" language="es-US">',
            'para español presione dos            </Say>',
            '</Gather>',
            '</Response>'
        ], false);
});
