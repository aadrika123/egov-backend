<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Trade\EloquentTrade;
use Illuminate\Http\Request;

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
    public function __construct(EloquentTrade $TradeRepository)
    {
        $this->Repository = $TradeRepository;
    }
    public function applyApplication(Request $request)
    {        
        return $this->Repository->applyApplication($request);
    }
}