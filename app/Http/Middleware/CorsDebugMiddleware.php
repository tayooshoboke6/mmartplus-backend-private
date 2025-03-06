<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorsDebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Log incoming request details
        if (str_contains($request->path(), 'email/non-auth')) {
            Log::info('DEBUG CORS: Incoming request', [
                'path' => $request->path(),
                'method' => $request->method(),
                'origin' => $request->header('Origin'),
                'content_type' => $request->header('Content-Type'),
                'all_headers' => $request->headers->all(),
            ]);
        }

        // Process the request
        $response = $next($request);

        // Log outgoing response details for the specific route
        if (str_contains($request->path(), 'email/non-auth')) {
            Log::info('DEBUG CORS: Outgoing response', [
                'path' => $request->path(),
                'status' => $response->status(),
                'headers' => $response->headers->all(),
            ]);
        }

        return $response;
    }
}
