<?php

namespace App\Services\Upgrade;

class UpgradeCheck
{
    public const STATUS_PASS = 'pass';
    public const STATUS_WARN = 'warn';
    public const STATUS_FAIL = 'fail';
    public const STATUS_SKIP = 'skip';

    public function __construct(
        public string $id,
        public string $label,
        public string $status,
        public ?string $message = null,
        public ?string $url = null,
    ) {
    }

    public static function pass(string $id, string $label, ?string $message = null): self
    {
        return new self($id, $label, self::STATUS_PASS, $message);
    }

    public static function warn(string $id, string $label, string $message, ?string $url = null): self
    {
        return new self($id, $label, self::STATUS_WARN, $message, $url);
    }

    public static function fail(string $id, string $label, string $message, ?string $url = null): self
    {
        return new self($id, $label, self::STATUS_FAIL, $message, $url);
    }

    public static function skip(string $id, string $label, string $message): self
    {
        return new self($id, $label, self::STATUS_SKIP, $message);
    }

    /**
     * @param array<string, mixed> $check
     */
    public static function fromPreflight(array $check): self
    {
        if ($check['passed']) {
            return self::pass($check['id'], $check['label'], $check['message']);
        }

        if (!$check['blocking']) {
            return self::warn(
                $check['id'],
                $check['label'],
                $check['message'],
            );
        }

        return self::fail($check['id'], $check['label'], $check['message']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'label' => $this->label,
            'status' => $this->status,
        ];

        if ($this->message !== null && $this->message !== '') {
            $result['message'] = $this->message;
        }

        if ($this->url !== null && $this->url !== '') {
            $result['url'] = $this->url;
        }

        return $result;
    }
}
