<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDetail;
use App\Models\ModuleMaster;
use App\Models\TcTracking;
use Exception;

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

    /**
     * | Tc Geo Location
     */
    public function tcGeoLocation(Request $request)
    {
        try {
            $userId = authUser()->id;
            $mTcTracking = new TcTracking();
            $mreqs = new Request([
                "user_id" => $userId,
                "lattitude" =>  $request->lattitude,
                "longitude" =>  $request->longitude,
            ]);
            $mTcTracking->store($mreqs);
            return responseMsgs(true, "location saved", "", "010203", "1.0", responseTime(), 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", responseTime(), 'POST', "");
        }
    }
}
