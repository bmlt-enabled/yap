<?php

namespace App\Services\Preflight;

class PreflightCheck
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $passed,
        public string $message,
        public ?string $remediation = null,
        public bool $blocking = true,
    ) {
    }

    public static function pass(
        string $id,
        string $label,
        string $message,
        bool $blocking = true,
    ): self {
        return new self($id, $label, true, $message, null, $blocking);
    }

    public static function fail(
        string $id,
        string $label,
        string $message,
        string $remediation,
        bool $blocking = true,
    ): self {
        return new self($id, $label, false, $message, $remediation, $blocking);
    }

    public static function warn(
        string $id,
        string $label,
        string $message,
        string $remediation,
    ): self {
        return new self($id, $label, false, $message, $remediation, false);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'passed' => $this->passed,
            'blocking' => $this->blocking,
            'message' => $this->message,
            'remediation' => $this->remediation,
        ];
    }
}
