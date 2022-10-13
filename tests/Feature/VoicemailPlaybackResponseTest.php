<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

// TODO: need a way to get google api keys
//test('voicemail playback response access denied', function () {
//    $_REQUEST['SpeechResult'] = "Raleigh, NC";
//    $response = $this->get('/voicemail-playback-response.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
//        ->assertSeeInOrder([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response>',
//            '<Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=&amp;Longitude=</Redirect>',
//            '</Response>',
//    ], false);
//});
