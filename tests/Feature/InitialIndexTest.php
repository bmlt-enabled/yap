<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('initial call-in default', function () {
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
    ], false);
});

test('initial call-in with jft option enabled', function () {
    $_SESSION['override_jft_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press three to listen to the just for today</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial call-in with spad option enabled', function () {
    $_SESSION['override_spad_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial call-in with jft and spad option enabled', function () {
    $_SESSION['override_jft_option'] = "true";
    $_SESSION['override_spad_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press three to listen to the just for today</Say>',
            '<Say voice="alice" language="en-US">',
            'press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial call-in default with language selections', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect>lng-selector.php</Redirect></Response>',
        ], false);
});

test('selected language call flow', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->call("GET", '/', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="es-US">presione uno para encontrar alguien con quien hablar</Say>',
            '<Say voice="alice" language="es-US">presione dos buscar reuniones</Say>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset', function () {
    $_SESSION['override_en_US_greeting'] = "https://example.org/fake.mp3";
    $response = $this->call("GET", '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset in a different language with selection menu', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call("GET", '/', [
        'Digits' => "2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset in a different language with single forced language', function () {
    $_SESSION['override_gather_language'] = "es-US";
    $_SESSION['override_word_language'] = "es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call("GET", '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});
