<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * | Author Name-Anshu Kumar
 * | Date-09-05-2023
 * | Status-Closed(09-05-2023)
 */

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

            if ($lastActivity && ($currentTime - $lastActivity) > 9000) {            // for 9000 Seconds(150 Minutes)
                Redis::del($key);
                $user->tokens()->delete();
                abort(response()->json(
                    [
                        'status' => true,
                        'authenticated' => false
                    ]
                ));
            }

            if (!$request->has('key') && !$request->input('heartbeat'))
                Redis::set($key, $currentTime);            // Caching
        }
        return $next($request);
    }
}
