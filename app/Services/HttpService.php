<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HttpService
{
    const USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +yap";

    public function get($url, $ttl = 0, $extraHeaders = []) : Response
    {
        $key = sprintf('cache_%s', $url);
        return cache()->remember($key, $ttl, function () use ($url, $extraHeaders) {
            $response = Http::withHeaders(array_merge([
                "User-Agent"=>self::USER_AGENT
            ], $extraHeaders))->get($url);
            return $response;
        });
    }

    public function getWithAuth($url, $ttl = 0) : Response
    {
        return $this->get($url, $ttl, ["Cookie", getBMLTAuthSessionCookies()]);
    }

    private function getBMLTAuthSessionCookies()
    {
        return isset($_SESSION['bmlt_auth_session']) ? implode(";", $_SESSION['bmlt_auth_session']) : "";
    }

}
