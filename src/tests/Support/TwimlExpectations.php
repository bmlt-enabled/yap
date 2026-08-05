<?php

namespace Tests\Support;

use PHPUnit\Framework\Assert;

class TwimlExpectations
{
    public static function assertRedirectsTo(string $twiml, string $uri): void
    {
        $xml = simplexml_load_string($twiml);
        $redirectElements = $xml->xpath('//Redirect');
        Assert::assertNotEmpty($redirectElements, 'Expected TwiML to contain <Redirect> tag');
        Assert::assertSame($uri, (string) $redirectElements[0], "Expected Redirect to point to {$uri}, got " . (string) $redirectElements[0]);
    }

    public static function assertGathersTo(string $twiml, string $action): void
    {
        $xml = simplexml_load_string($twiml);
        $elements = $xml->xpath('//Gather');
        Assert::assertNotEmpty($elements, 'Expected TwiML to contain <Gather> tag');

        $found = false;
        foreach ($elements as $element) {
            if ((string) $element['action'] === $action) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, "Expected <Gather action=\"{$action}\"> but got:\n{$twiml}");
    }

    public static function assertDialsConference(string $twiml, ?string $conferenceName = null): string
    {
        $xml = simplexml_load_string($twiml);
        $elements = $xml->xpath('//Conference');
        Assert::assertNotEmpty($elements, "Expected TwiML to contain <Conference> but got:\n{$twiml}");

        $name = trim((string) $elements[0]);
        if ($conferenceName !== null) {
            Assert::assertSame($conferenceName, $name, "Expected conference name {$conferenceName}, got {$name}");
        }

        return $name;
    }

    public static function getAttributeFromTag(string $twiml, string $tag, string $attribute): string
    {
        $xml = simplexml_load_string($twiml);
        $elements = $xml->xpath('//' . $tag);
        Assert::assertNotEmpty($elements, "Cannot find {$attribute} - no {$tag} tag found");
        Assert::assertTrue(isset($elements[0][$attribute]), "Cannot find {$attribute} attribute in {$tag} tag");

        return (string) $elements[0][$attribute];
    }

    public static function assertTwimlContains(string $twiml, string $tag, array $attributes = [], ?string $content = null): void
    {
        $xml = simplexml_load_string($twiml);
        $elements = $xml->xpath('//' . $tag);
        Assert::assertNotEmpty($elements, "Expected TwiML to contain <{$tag}> but got:\n{$twiml}");

        if (empty($attributes) && $content === null) {
            return;
        }

        $found = false;
        foreach ($elements as $element) {
            $elementAttributes = [];
            foreach ($element->attributes() as $key => $value) {
                $elementAttributes[$key] = (string) $value;
            }

            $matchesAttributes = empty($attributes) || empty(array_diff_assoc($attributes, $elementAttributes));
            $matchesContent = $content === null || trim((string) $element) === $content;

            if ($matchesAttributes && $matchesContent) {
                $found = true;
                break;
            }
        }

        $errorMessage = "Expected <{$tag}>";
        if (!empty($attributes)) {
            $errorMessage .= ' with attributes ' . json_encode($attributes);
        }
        if ($content !== null) {
            $errorMessage .= " containing text '{$content}'";
        }
        $errorMessage .= " but got:\n{$twiml}";

        Assert::assertTrue($found, $errorMessage);
    }
}
