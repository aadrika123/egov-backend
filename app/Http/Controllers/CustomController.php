<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDetail;
use App\Models\ModuleMaster;

class CustomController extends Controller
{
    public function getCustomDetails(Request $request)
    {
        $mCustomDetail = new CustomDetail();
        return $mCustomDetail->getCustomDetails($request);
    }

    //post custom details
    public function postCustomDetails(Request $request)
    {
        $mCustomDetail = new CustomDetail();
        return $mCustomDetail->postCustomDetails($request);
    }

    /**
     * | Get Dues Api
     */
    public function duesApi(Request $request)
    {
        $mModuleMaster = new ModuleMaster();
        $duesApi = $mModuleMaster->duesApi($request);
        return responseMsgs(true, "Dues Api", $duesApi, "", 01, responseTime(), "POST", $request->deviceId);
    }
}
