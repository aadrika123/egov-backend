<?php

namespace App\Repository\Trade;

use Illuminate\Http\Request;

class EloquentTrade implements TradeRepository
{

    public function __construct()
    {

    }
    public function applyApplication(Request $request)
    {
        dd($request->applicationType);
    }
    
}