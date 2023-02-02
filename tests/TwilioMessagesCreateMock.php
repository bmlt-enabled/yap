<?php

namespace Tests;

class TwilioMessagesCreateMock
{
    private $twilioClient = null;

    public function __construct($body)
    {
        $fakeHttpClient = new FakeTwilioHttpClient();
        $this->twilioClient = mock('Twilio\Rest\Client', [
            "username" => "fake",
            "password" => "fake",
            "httpClient" => $fakeHttpClient
        ]);

        // mocking TwilioRestClient->messages->create()
        $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
        $messageListMock->shouldReceive('create')
            ->with(is_string(""), is_array(['body' => $body]));
        $this->twilioClient->messages = $messageListMock;
        $GLOBALS['twilioClient'] = $this->twilioClient;
    }
}
