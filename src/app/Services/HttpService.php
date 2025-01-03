<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HttpService
{
    const USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 +yap";

    public function get($url, $ttl = 0, $extraHeaders = []) : string
    {
        $key = sprintf('cache_%s', $url);
        return cache()->remember($key, $ttl, function () use ($url, $extraHeaders) {
            return Http::withHeaders(array_merge([
                "User-Agent" => self::USER_AGENT
            ], $extraHeaders))->get($url)->body();
        });
    }

    public function post($url, $data, $extraHeaders = []) : Response
    {
        return Http::withHeaders(array_merge([
            "User-Agent"=>self::USER_AGENT
        ], $extraHeaders))->post($url, $data);
    }

    public function postAsForm($url, $data, $extraHeaders = []) : Response
    {
        return Http::withHeaders(array_merge([
            "User-Agent"=>self::USER_AGENT
        ], $extraHeaders))->asForm()->post($url, $data);
    }

    public function getWithAuth($url, $ttl = 0) : string
    {
        return $this->get($url, $ttl, ["Cookie" => $this->getBMLTAuthSessionCookies()]);
    }

    private function getBMLTAuthSessionCookies(): string
    {
        return session()->has('bmlt_auth_session') ? implode(";", session()->get('bmlt_auth_session')) : "";
    }
}
