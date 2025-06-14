<?php

namespace Tests;

use Illuminate\Testing\TestResponse;
use Illuminate\Support\Str;
use Twilio\Rest\Client;
use DateTime;

class TwilioCallTestBuilder
{
    protected string $callSid;
    protected string $phoneNumberSid;
    protected string $fromNumber;
    protected string $toNumber = '+15553334444'; // Default app number
    protected array $callData = [];
    protected ?TestResponse $lastResponse = null;
    protected Client $twilioClient;
    protected TwilioTestUtility $utility;

    public function __construct(array $settings)
    {
        $this->callSid = 'CA' . Str::uuid()->toString();
        $this->phoneNumberSid = 'PN' . Str::uuid()->toString();
        $this->utility = setupTwilioService();
        foreach ($settings as $key => $value) {
            $this->utility->settings->set($key, $value);
        }
    }

    public function startCall(string $fromNumber, string $method = 'GET'): self
    {
        $this->fromNumber = $fromNumber;

        $this->callData = [
            'CallSid' => $this->callSid,
            'From' => $fromNumber,
            'To' => $this->toNumber,
        ];

        $this->utility->twilio->client()->shouldReceive('getAccountSid')->andReturn("123");
        // mocking TwilioRestClient->calls()->fetch()->phoneNumberSid
        $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
        $callInstance->startTime = new DateTime('2023-01-26T18:00:00');
        $callInstance->endTime = new DateTime('2023-01-26T18:15:00');
        $callInstance->phoneNumberSid = $this->phoneNumberSid;
    
        $callContext = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
        $callContext->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
        $this->utility->twilio->client()->shouldReceive('calls')
            ->withArgs([$this->callSid])->andReturn($callContext)->once();
    
        $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberContext');
        $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
        $incomingPhoneNumberInstance->statusCallback = "blah.php";
        $incomingPhoneNumberInstance->phoneNumber = $this->toNumber;
        $incomingPhoneNumberContext->shouldReceive('fetch')->withNoArgs()
            ->andReturn($incomingPhoneNumberInstance)->once();
    
        // mocking TwilioRestClient->incomingPhoneNumbers()->fetch();
        $this->utility->twilio->client()->shouldReceive('incomingPhoneNumbers')
            ->withArgs([$this->phoneNumberSid])->andReturn($incomingPhoneNumberContext)->once();

        $this->lastResponse = test()->call($method, '/index.php', $this->callData);
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function setSetting(string $key, string $value): self
    {
        $this->utility->settings->set($key, $value);
        return $this;
    }

    public function expectCallRedirect(string $uri): self
    {
        $xml = simplexml_load_string($this->lastResponse->getContent());
        $redirectElements = $xml->xpath('//Redirect');
        assert(!empty($redirectElements), "Expected TwiML to contain <Redirect> tag");
        assert((string)$redirectElements[0] === $uri, "Expected Redirect to point to {$uri}, got " . (string)$redirectElements[0]);
        return $this;
    }

    protected function assertTwimlContains(string $tag): void
    {
        $xml = simplexml_load_string($this->lastResponse->getContent());
        $tags = array_map(fn($e) => $e->getName(), iterator_to_array($xml));
        $actualContent = $this->lastResponse->getContent();
        assert(in_array($tag, $tags), "Expected TwiML to contain <$tag> but got: \n$actualContent");
    }
}