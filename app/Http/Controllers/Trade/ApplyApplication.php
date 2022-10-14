<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Trade\ITrade;
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
    private $Repository;
    public function __construct(ITrade $TradeRepository)
    {
        // $this->middleware(function ($request, $next) use($TradeRepository) {          
        //     $virtualRole = User::first();
        //     $user = auth()->user() ?? $virtualRole;
        //     $TradeRepository->__construct($user);
        //     $this->Repository = $TradeRepository ;
        //     return $next($request);
        // });
        $this->Repository = $TradeRepository ;
    }
    public function applyApplication(Request $request)
    {        
        return $this->Repository->applyApplication($request);
    }
    public function paybleAmount(Request $request)
    {      
        return $this->Repository->paybleAmount($request);
    }
    public function validate_holding_no(Request $request)
    {
        return $this->Repository->validate_holding_no($request);
    }
    public function paymentRecipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        return $this->Repository->paymentRecipt($id,$transectionId);
    }
    public function updateBasicDtl(Request $request)
    {
        return $this->Repository->updateBasicDtl($request);
    }
    public function getLicenceDtl(Request $request)
    {
        return $this->Repository->getLicenceDtl($request->id);
    }
}