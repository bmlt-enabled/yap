<?php

namespace App\Simulator;

use Twilio\Security\RequestValidator;

/**
 * Plays the part of Twilio against a running yap instance.
 *
 * It sends signed, Twilio-shaped webhooks, executes the TwiML that comes back
 * (following <Redirect>, feeding <Gather> scripted digits or speech, joining
 * <Conference>s), and reads back the outbound REST requests the app made
 * through the fake Twilio client. The instance must run with TWILIO_FAKE_MODE
 * enabled so those outbound requests are captured instead of hitting Twilio.
 */
class TwilioSimulator
{
    private const CONTROL_JAR = '__fake_twilio_control__';

    /** @var array<string, int> conference friendly name => last SequenceNumber sent */
    private array $conferenceSequence = [];

    /** @var callable(string): void */
    private $logger;

    public function __construct(
        private string $baseUrl,
        private string $accountSid,
        private string $authToken,
        private Transport $transport,
        ?callable $logger = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = $logger ?? fn (string $line) => null;
    }

    public function accountSid(): string
    {
        return $this->accountSid;
    }

    public function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function log(string $line): void
    {
        ($this->logger)($line);
    }

    /**
     * Send one webhook the way Twilio does: GET params go in the query string,
     * POST params in a form body, and X-Twilio-Signature is an HMAC-SHA1 over
     * the URL (plus sorted POST params) keyed by the account's auth token.
     *
     * @param array<string, scalar|array> $params
     * @param array<string, string> $headers
     */
    public function webhook(string $method, string $url, array $params, string $jar, array $headers = []): SimulatorResponse
    {
        $method = strtoupper($method);
        $url = $this->matchBaseScheme($url);
        if ($method === 'GET') {
            $url = UrlResolver::withQuery($url, $params);
            $params = [];
        } else {
            $url = UrlResolver::normalize($url);
        }

        $headers['X-Twilio-Signature'] = (new RequestValidator($this->authToken))->computeSignature($url, $params);
        $headers['User-Agent'] ??= 'TwilioProxy/1.1 (yap simulator)';

        $this->log("→ {$method} {$url}" . ($params === [] ? '' : ' ' . http_build_query($params)));
        $response = $this->transport->send($method, $url, $params, $headers, $jar);
        $this->log("← {$response->status}");

        return $response;
    }

    /**
     * yap builds some absolute callback URLs (volunteer outdial, status
     * callbacks) with a hard-coded https:// scheme. Against a plain-http dev
     * server those would never connect, so a URL on the target's own host is
     * sent with the base URL's scheme instead. Real Twilio needs https anyway.
     */
    private function matchBaseScheme(string $url): string
    {
        $base = parse_url($this->baseUrl);
        $target = parse_url($url);
        if (!isset($target['scheme'], $target['host'], $base['scheme'], $base['host'])
            || $target['scheme'] === $base['scheme']
            || $target['host'] !== $base['host']) {
            return $url;
        }

        $rewritten = $base['scheme'] . substr($url, strlen($target['scheme']));
        if (isset($base['port']) && !isset($target['port'])) {
            $rewritten = preg_replace('#^(\w+://[^/:?]+)#', '$1:' . $base['port'], $rewritten, 1);
        }
        $this->log("  ↺ {$target['scheme']}:// callback sent as {$base['scheme']}:// to match --base-url");

        return $rewritten;
    }

    /**
     * Call one of the /fake-twilio/* control endpoints.
     *
     * @param array<string, scalar> $params
     */
    public function control(string $method, string $path, array $params = []): array
    {
        $response = $this->webhook($method, $this->url('fake-twilio/' . $path), $params, self::CONTROL_JAR, [
            'Accept' => 'application/json',
        ]);

        if ($response->status !== 200) {
            throw new SimulationFailed(sprintf(
                "Fake Twilio control endpoint %s %s returned HTTP %d. Is the instance running with TWILIO_FAKE_MODE=true "
                . "(and not APP_ENV=production), and does --auth-token match its twilio_auth_token?\n%s",
                $method,
                $path,
                $response->status,
                $response->body,
            ));
        }

        return $response->json();
    }

    public function reset(): void
    {
        $this->control('POST', 'reset');
        $this->conferenceSequence = [];
    }

    /**
     * Outbound Twilio REST requests the app has made, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function outbound(?string $resource = null, int $after = 0): array
    {
        $params = ['after' => $after];
        if ($resource !== null) {
            $params['resource'] = $resource;
        }

        return $this->control('GET', 'requests', $params)['requests'];
    }

    public function lastOutboundId(): int
    {
        $requests = $this->outbound();

        return $requests === [] ? 0 : (int) end($requests)['id'];
    }

    /**
     * Place an inbound call to $to from $from and run it up to the first point
     * where the caller has to do something.
     *
     * @param array<string, scalar|array> $query extra query string for the first webhook,
     *                                           e.g. override_* settings for the call
     * @param array<string, scalar> $params extra webhook params, e.g. StirVerstat
     */
    public function startCall(string $from, string $to, array $query = [], array $params = [], string $path = 'index.php'): SimulatedCall
    {
        $sid = 'CA' . bin2hex(random_bytes(16));
        $this->control('POST', 'calls', ['sid' => $sid, 'from' => $from, 'to' => $to]);
        $this->log("☎ inbound call {$sid} {$from} → {$to}");

        // Twilio numbers pointed at yap send call progress to status.php.
        $call = new SimulatedCall($this, $sid, $from, $to, 'inbound', statusCallback: $this->url('status.php'));

        return $call->request('POST', UrlResolver::withQuery($this->url($path), $query), $params);
    }

    /**
     * Deliver an inbound SMS to the gateway and run the TwiML it returns.
     *
     * @param array<string, scalar|array> $query
     */
    public function sendSms(string $from, string $to, string $body, array $query = [], string $path = 'sms-gateway.php'): SimulatedMessage
    {
        $message = new SimulatedMessage($this, 'SM' . bin2hex(random_bytes(16)), $from, $to, $body);
        $this->log("✉ inbound SMS {$from} → {$to}: {$body}");

        return $message->deliver(UrlResolver::withQuery($this->url($path), $query));
    }

    /**
     * Outbound calls the app has placed (calls->create) since request id $after,
     * as call legs the simulator can answer, reject or leave ringing.
     *
     * @return list<SimulatedCall>
     */
    public function outboundCalls(int $after = 0): array
    {
        $legs = [];
        foreach ($this->outbound('calls.create', $after) as $request) {
            $data = $request['data'];
            $legs[] = new SimulatedCall(
                $this,
                (string) ($request['response']['sid'] ?? ''),
                (string) ($data['From'] ?? ''),
                (string) ($data['To'] ?? ''),
                'outbound-api',
                $data['Url'] ?? null,
                $data['Method'] ?? 'POST',
                $data['StatusCallback'] ?? null,
                $data['StatusCallbackMethod'] ?? 'POST',
            );
        }

        return $legs;
    }

    public function joinConference(string $friendlyName, string $callSid): array
    {
        $this->log("⇢ {$callSid} joins conference {$friendlyName}");

        return $this->control('POST', 'conferences/join', ['friendly_name' => $friendlyName, 'call_sid' => $callSid]);
    }

    public function leaveConference(string $friendlyName, string $callSid): array
    {
        $this->log("⇠ {$callSid} leaves conference {$friendlyName}");

        return $this->control('POST', 'conferences/leave', ['friendly_name' => $friendlyName, 'call_sid' => $callSid]);
    }

    public function nextConferenceSequence(string $friendlyName): int
    {
        return $this->conferenceSequence[$friendlyName] = ($this->conferenceSequence[$friendlyName] ?? 0) + 1;
    }

    public function markCallStatus(string $sid, string $from, string $to, string $status): void
    {
        $this->control('POST', 'calls', ['sid' => $sid, 'from' => $from, 'to' => $to, 'status' => $status]);
    }
}
