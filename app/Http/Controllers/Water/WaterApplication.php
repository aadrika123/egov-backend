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
    
}
