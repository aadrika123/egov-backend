<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
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
        // Returns boolean
        if ($request->headers->has('API-KEY') == false) {
            return response()->json([
                'status' => false,
                'message' => 'No Authorization Key',
            ], 400);
        };

        // Returns header value with default as fallback
        $val = $request->header('API-KEY', 'default_value');
        if ($val === 'eff41ef6-d430-4887-aa55-9fcf46c72c99') {
            return $next($request);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Auth Key',
            ], 400);
        }
    }
}
