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

    public function startCall(string $fromNumber, string $toNumber, string $method = 'GET'): self
    {
        $this->callData = [
            'CallSid' => $this->callSid,
            'Called' => $toNumber,
            'From' => $fromNumber,
            'To' => $toNumber,
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
            ->withArgs([$this->callSid])->andReturn($callContext); //->once();
    
        $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberContext');
        $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
        $incomingPhoneNumberInstance->statusCallback = "blah.php";
        $incomingPhoneNumberInstance->phoneNumber = $toNumber;
        $incomingPhoneNumberContext->shouldReceive('fetch')->withNoArgs()
            ->andReturn($incomingPhoneNumberInstance); //->once();
    
        // mocking TwilioRestClient->incomingPhoneNumbers()->fetch();
        $this->utility->twilio->client()->shouldReceive('incomingPhoneNumbers')
            ->withArgs([$this->phoneNumberSid])->andReturn($incomingPhoneNumberContext); //->once();

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
    
    public function printLastResponse(): self
    {
        echo $this->lastResponse->getContent();
        return $this;
    }

    public function followRedirect(): self
    {
        $xml = simplexml_load_string($this->lastResponse->getContent());
        $redirectElements = $xml->xpath('//Redirect');
        assert(!empty($redirectElements), "Cannot follow redirect - no Redirect tag found");
        $uri = (string)$redirectElements[0];
        $this->lastResponse = $this->call('GET', $uri, $this->callData);
        $this->lastResponse->assertStatus(200);
        return $this;
    }

    protected function getAttributeFromTag(string $tag, string $attribute): string
    {
        $xml = simplexml_load_string($this->lastResponse->getContent());
        $elements = $xml->xpath('//' . $tag);
        assert(!empty($elements), "Cannot find {$attribute} - no {$tag} tag found");
        assert(isset($elements[0][$attribute]), "Cannot find {$attribute} attribute in {$tag} tag");
        return (string)$elements[0][$attribute];
    }

    public function pressDigits(string $digits): self
    {
        $this->callData['Digits'] = $digits;
        $action = $this->getAttributeFromTag('Gather', 'action');
        $this->callData['action'] = $action;
        $this->lastResponse = $this->call('GET', $this->callData['action'], $this->callData);
        $this->lastResponse->assertStatus(200);
        return $this;
    }

    public function expectTwimlContains(string $tag, array $attributes = [], ?string $content = null): self
    {
        $xml = simplexml_load_string($this->lastResponse->getContent());
        $elements = $xml->xpath('//' . $tag);
        $actualContent = $this->lastResponse->getContent();
        assert(!empty($elements), "Expected TwiML to contain <{$tag}> but got:\n{$actualContent}");
        
        if (!empty($attributes) || $content !== null) {
            $found = false;
            foreach ($elements as $element) {
                $elementAttributes = [];
                foreach ($element->attributes() as $key => $value) {
                    $elementAttributes[$key] = (string)$value;
                }
                
                $matchesAttributes = empty($attributes) || empty(array_diff_assoc($attributes, $elementAttributes));
                $matchesContent = $content === null || trim((string)$element) === $content;
                
                if ($matchesAttributes && $matchesContent) {
                    $found = true;
                    break;
                }
            }
            
            $errorMessage = "Expected <{$tag}>";
            if (!empty($attributes)) {
                $errorMessage .= " with attributes " . json_encode($attributes);
            }
            if ($content !== null) {
                $errorMessage .= " containing text '{$content}'";
            }
            $errorMessage .= " but got:\n{$actualContent}";
            
            assert($found, $errorMessage);
        }
        
        return $this;
    }

    protected function call($method, string $uri, array $data): TestResponse
    {
        return test()->call($method, $uri, $data);
    }
}
