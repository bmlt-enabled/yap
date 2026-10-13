<?php

namespace App\Simulator;

/**
 * A parsed TwiML document: the ordered list of verbs under <Response>.
 */
class Twiml
{
    /** @param list<TwimlVerb> $verbs */
    public function __construct(public readonly array $verbs, public readonly string $raw)
    {
    }

    public static function parse(string $body): self
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false || $xml->getName() !== 'Response') {
            throw new SimulationFailed("Expected a TwiML <Response>, got:\n" . $body);
        }

        $verbs = [];
        foreach ($xml->children() as $child) {
            $verbs[] = TwimlVerb::fromXml($child);
        }

        return new self($verbs, $body);
    }

    public function first(string $name): ?TwimlVerb
    {
        foreach ($this->verbs as $verb) {
            if ($verb->name === $name) {
                return $verb;
            }
        }

        return null;
    }

    public function has(string $name): bool
    {
        return $this->first($name) !== null;
    }

    /** @return list<string> */
    public function says(): array
    {
        $says = [];
        foreach ($this->verbs as $verb) {
            array_push($says, ...$verb->says());
        }

        return $says;
    }

    /**
     * The verbs Twilio would run after $verb finishes without redirecting, e.g.
     * what follows a <Gather> that timed out with no input.
     */
    public function after(TwimlVerb $verb): self
    {
        $index = array_search($verb, $this->verbs, true);

        return new self($index === false ? [] : array_slice($this->verbs, $index + 1), $this->raw);
    }
}
