<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Trade\EloquentTrade;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ApplyApplication extends Controller
{

    /**
     * | Created On-01-10-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository
    protected $TradeRepository;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $virtualRole = User::first();
            $user = auth()->user() ?? $virtualRole;
            $obj = new EloquentTrade($user);
            $this->Repository = $obj;
            return $next($request);
        });
    }
    public function applyApplication(Request $request)
    {        
        return $this->Repository->applyApplication($request);
    }
}