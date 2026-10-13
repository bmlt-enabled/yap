<?php

namespace App\Simulator;

/**
 * One TwiML verb or noun, e.g. <Gather action="..."> or <Conference>name</Conference>.
 */
class TwimlVerb
{
    /**
     * @param array<string, string> $attributes
     * @param list<TwimlVerb> $children
     */
    public function __construct(
        public readonly string $name,
        public readonly array $attributes,
        public readonly string $text,
        public readonly array $children,
    ) {
    }

    public static function fromXml(\SimpleXMLElement $element): self
    {
        $attributes = [];
        foreach ($element->attributes() as $key => $value) {
            $attributes[(string) $key] = (string) $value;
        }

        $children = [];
        foreach ($element->children() as $child) {
            $children[] = self::fromXml($child);
        }

        return new self($element->getName(), $attributes, trim((string) $element), $children);
    }

    public function attr(string $name, ?string $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }

    public function child(string $name): ?self
    {
        foreach ($this->children as $child) {
            if ($child->name === $name) {
                return $child;
            }
        }

        return null;
    }

    /** @return list<string> text of every <Say> at or below this verb */
    public function says(): array
    {
        $says = $this->name === 'Say' ? [$this->text] : [];
        foreach ($this->children as $child) {
            array_push($says, ...$child->says());
        }

        return $says;
    }
}
