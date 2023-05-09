<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ExpireBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $token = $request->bearerToken();
        $currentTime = time();

        if ($user && $token) {
            $key = 'last_activity_' . $user->id;
            $lastActivity = Redis::get($key);

            if ($lastActivity && ($currentTime - $lastActivity) > 600) {
                Redis::del($key);
                $user->tokens()->delete();
                abort(response()->json(['error' => 'Unauthenticated.'], 401));
            }

            Redis::setex($key, 600, $currentTime);            // Caching
        }
        return $next($request);
    }
}
