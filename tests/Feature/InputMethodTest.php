<?php

use App\Constants\SearchType;
use App\Repositories\ReportsRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('search for volunteers', function () {
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by city or county</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('search for meetings', function () {
    $reportsRepository = Mockery::mock(ReportsRepository::class);
    $reportsRepository->shouldReceive("insertCallEventRecord")->withAnyArgs()->once();
    app()->instance(ReportsRepository::class, $reportsRepository);

    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for meetings by city or county</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('search for meetings, disable postal code gathering', function () {
    $_SESSION['override_disable_postal_code_gather'] = true;
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method-result.php?SearchType=2&amp;Digits=1</Redirect>',
            '</Response>'
        ], false);
});

test('direct to volunteer search for a specific service body', function () {
    $_SESSION['override_service_body_id'] = 44;
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"1",
        "Called"=>"123"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Called=123</Redirect>',
            '</Response>'
        ], false);
});

test('search for volunteers without custom query', function () {
    $_SESSION['override_custom_query'] = '&services=92';
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"2",
        "Called"=>"123"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?Called=123</Redirect>',
            '</Response>'
        ], false);
});

test('jft option enabled and selected', function () {
    $_SESSION['override_jft_option'] = true;
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"3"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-jft.php</Redirect>',
            '</Response>'
        ], false);
});

test('spad option enabled and selected', function () {
    $_SESSION['override_spad_option'] = true;
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"4"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-spad.php</Redirect>',
            '</Response>'
        ], false);
});

test('dialback selected', function () {
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"9"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">dialback.php</Redirect>',
            '</Response>'
        ], false);
});

test('custom extension configured and selected', function () {
    $_SESSION['override_custom_extensions'] = [7 => '12125551212'];
    $_SESSION['override_digit_map_search_type'] = [
        '1' => SearchType::VOLUNTEERS,
        '2' => SearchType::MEETINGS,
        '3' => SearchType::JFT,
        '4' => SearchType::SPAD,
        '7' => SearchType::CUSTOM_EXTENSIONS,
        '9' => SearchType::DIALBACK
    ];

    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"7"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">custom-ext.php</Redirect>',
            '</Response>'
        ], false);
});

test('invalid search', function () {
    $response = $this->call('GET', '/input-method.php', [
        "Digits"=>"5"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect>index.php</Redirect>',
            '</Response>'
        ], false);
});
