<?php

namespace App\Services\Preflight;

class PhpExtensionRequirements
{
    /**
     * Extensions Yap and Laravel 12 require at runtime.
     * Keys are extension_loaded() names; values are short descriptions for operators.
     *
     * @var array<string, string>
     */
    public const REQUIRED = [
        'pdo' => 'PDO database abstraction',
        'pdo_mysql' => 'MySQL driver for PDO',
        'fileinfo' => 'File type detection (provides the finfo class; required by Laravel)',
        'mbstring' => 'Multibyte string handling',
        'openssl' => 'HTTPS and cryptography',
        'curl' => 'HTTP client for Twilio, BMLT, and Google Maps API calls',
        'json' => 'JSON encoding and decoding',
        'ctype' => 'Character type checking',
        'filter' => 'Input filtering',
        'hash' => 'Hashing functions',
        'session' => 'Session support',
        'tokenizer' => 'PHP tokenizer (required by Laravel)',
        'xml' => 'XML parsing (libxml)',
        'dom' => 'DOM XML (libxml)',
    ];

    /**
     * Extensions that improve compatibility but are not hard requirements today.
     *
     * @var array<string, string>
     */
    public const RECOMMENDED = [
        'iconv' => 'Character set conversion',
        'intl' => 'Internationalized domain names in outbound HTTP',
    ];
}
