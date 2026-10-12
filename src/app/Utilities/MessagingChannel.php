<?php

namespace App\Utilities;

class MessagingChannel
{
    public const SMS = 'sms';
    public const WHATSAPP = 'whatsapp';
    public const PREFIX = 'whatsapp:';

    public static function isWhatsApp(?string $address): bool
    {
        if ($address === null || $address === '') {
            return false;
        }

        return str_starts_with(strtolower($address), self::PREFIX);
    }

    public static function e164(?string $address): ?string
    {
        if ($address === null || $address === '') {
            return $address;
        }

        $trimmed = trim($address);
        if (str_starts_with(strtolower($trimmed), self::PREFIX)) {
            return substr($trimmed, strlen(self::PREFIX));
        }

        return $trimmed;
    }

    public static function channelFromAddresses(?string ...$addresses): string
    {
        foreach ($addresses as $address) {
            if (self::isWhatsApp($address)) {
                return self::WHATSAPP;
            }
        }

        return self::SMS;
    }

    public static function sameNumber(?string $a, ?string $b): bool
    {
        $left = self::e164($a);
        $right = self::e164($b);

        return $left !== null && $right !== null && $left !== '' && $left === $right;
    }

    public static function clickToChatUrl(?string $address, ?string $prefill = null): ?string
    {
        $e164 = self::e164($address);
        if ($e164 === null || $e164 === '') {
            return null;
        }

        $url = 'https://wa.me/' . ltrim($e164, '+');
        if (is_string($prefill) && $prefill !== '') {
            $url .= '?text=' . rawurlencode($prefill);
        }

        return $url;
    }
}
