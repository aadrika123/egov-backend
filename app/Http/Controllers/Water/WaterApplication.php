<?php

namespace App\Http\Controllers\water;

use App\Http\Controllers\Controller;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use Illuminate\Http\Request;

class WaterApplication extends Controller
{
    private $Repository;
    public function __construct(IWaterNewConnection $Repository)
    {
        $this->Repository = $Repository ;
    }

    public function applyApplication(Request $request)
    {
        return $this->Repository->applyApplication($request);
    }
    public function getCitizenApplication(Request $request)
    {
        return $this->Repository->getCitizenApplication($request);
    }
    public function handeRazorPay(Request $request)
    {
        return $this->Repository->handeRazorPay($request);
    }
    public function readTransectionAndApl(Request $request)
    {
        return $this->Repository->readTransectionAndApl($request);
    }
    public function paymentRecipt(Request $request)
    {
        return $this->Repository->paymentRecipt($request->id,$request->transectionId);
    }
    
}
