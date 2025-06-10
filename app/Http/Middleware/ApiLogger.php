<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiLogger
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $method = $request->method();
        $url = $request->fullUrl();

        $response = $next($request);

        // Capture response content
        $responseContent = json_decode($response->getContent(), true);

        // Log based on success or failure
        if (isset($responseContent['status']) && $responseContent['status'] === false) {
            Log::channel('apilogs')->error('❌ API failed', [
                'url' => $url,
                'ip' => $ip,
                'method' => $method,
                'error' => $responseContent['message'] ?? 'Unknown error',
            ]);
        } else {
            Log::channel('apilogs')->info('✅ API success', [
                'url' => $url,
                'ip' => $ip,
                'method' => $method,
            ]);
        }

        return $response;
    }
}
