<?php

namespace App\Simulator;

/**
 * One inbound SMS delivered to the gateway webhook. Replies come back either as
 * <Message> TwiML or, for yap's meeting search, as outbound REST messages the
 * fake Twilio client captures; a <Redirect> is followed as Twilio Messaging does.
 */
class SimulatedMessage
{
    /** @var list<string> */
    private array $replies = [];

    private ?string $currentUrl = null;

    public function __construct(
        private TwilioSimulator $simulator,
        public readonly string $sid,
        public readonly string $from,
        public readonly string $to,
        public readonly string $body,
    ) {
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return [
            'AccountSid' => $this->simulator->accountSid(),
            'ApiVersion' => '2010-04-01',
            'MessageSid' => $this->sid,
            'SmsSid' => $this->sid,
            'SmsMessageSid' => $this->sid,
            'From' => $this->from,
            'To' => $this->to,
            'Body' => $this->body,
            'NumMedia' => '0',
            'NumSegments' => '1',
        ];
    }

    public function deliver(string $url, string $method = 'POST', int $redirects = 0): self
    {
        if ($redirects > 10) {
            throw new SimulationFailed("SMS {$this->sid} redirected more than 10 times.");
        }

        $response = $this->simulator->webhook($method, $url, $this->params(), $this->sid);
        if ($response->status !== 200) {
            throw new SimulationFailed("{$method} {$url} returned HTTP {$response->status} for SMS {$this->sid}:\n{$response->body}");
        }

        $this->currentUrl = $response->url;
        $twiml = Twiml::parse($response->body);

        foreach ($twiml->verbs as $verb) {
            if ($verb->name === 'Message' || $verb->name === 'Sms') {
                $text = $verb->child('Body')?->text ?? $verb->text;
                $this->replies[] = $text;
                $this->simulator->log("  ✉ reply: {$text}");
            } elseif ($verb->name === 'Redirect') {
                return $this->deliver(
                    UrlResolver::resolve($this->currentUrl, $verb->text),
                    $verb->attr('method', 'POST'),
                    $redirects + 1,
                );
            }
        }

        return $this;
    }

    /** @return list<string> <Message> replies returned in TwiML */
    public function replies(): array
    {
        return $this->replies;
    }
}
