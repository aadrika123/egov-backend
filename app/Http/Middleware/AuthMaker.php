<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMaker
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
        if (!Auth()->user() && $request->auth) {
            if (is_array($request->auth))
                $auth = (object)$request->auth;
            else
                $auth = json_decode($request->auth);
            if (is_array($request->currentAccessToken))
                $cat = $request->currentAccessToken;
            else
                $cat = json_decode($request->currentAccessToken);

            switch ($cat) {
                case "App\\Models\\Auth\\User":
                    Auth::login(new \App\Models\User((array)$auth));
                    Auth()->user()->ulb_id = $auth->ulb_id;
                    break;
                default:
                    Auth::login(new \App\Models\ActiveCitizen((array)$auth));
                    break;
            }
            collect($request->auth)->map(function ($val, $key) {
                Auth()->user()->$key = $val;
            });
            Auth()->user()->id = $auth->id;
        }
        return $next($request);
    }
}
