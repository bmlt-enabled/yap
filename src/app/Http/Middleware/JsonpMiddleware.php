<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Check if the request has a 'callback' parameter and the response is in JSON format
        if ($request->has('callback') && $response->headers->get('content-type') === 'application/json') {
            $callback = $request->input('callback');
            $content = $response->getContent();

            // Wrap the JSON response in the callback function
            $jsonpResponse = sprintf('%s(%s);', $callback, $content);

            // Set the new content and content type
            $response->setContent($jsonpResponse);
            $response->headers->set('Content-Type', 'application/javascript');
        }

        return $response;
    }
}
