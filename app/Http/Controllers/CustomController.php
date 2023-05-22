<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDetail;
use App\Models\ModuleMaster;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropTransaction;
use App\Models\QuickAccessMaster;
use App\Models\QuickaccessUserMap;
use App\Models\TcTracking;
use Exception;
use Illuminate\Support\Facades\Validator;

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
        $duesApi = $mModuleMaster->duesApi();
        return responseMsgs(true, "Dues Api", $duesApi, "", 01, responseTime(), "POST", $request->deviceId);
    }

    /**
     * | Tc Geo Location
     */
    public function tcGeoLocation(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
                "lattitude" => "required",
                "longitude" => "required"
            ]
        );
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validate->errors()
            ], 422);
        }
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

    /**
     * | locationList
     */
    public function locationList(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
                "date" => "required|date",
            ]
        );
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validate->errors()
            ], 422);
        }
        try {
            $userId = $request->userId ?? authUser()->id;
            $mTcTracking = new TcTracking();
            $data = $mTcTracking->getLocationByUserId($userId, $request->date);
            return responseMsgs(true, "location list", $data, "010203", "1.0", responseTime(), 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $data, "010203", "1.0", responseTime(), 'POST', "");
        }
    }

    /**
     * | Tc Route
     */
    public function tcCollectionRoute(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            ["date" => "required|date"]
        );
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validate->errors()
            ], 422);
        }
        try {
            $userId = $request->userId ?? authUser()->id;
            $mTcTracking = new TcTracking();
            $mPropTransaction = new PropTransaction();
            $tranDtls = $mPropTransaction->getPropTransactions($request->date, "tran_date");
            if ($tranDtls->isEmpty())
                throw new Exception('No Transaction Found Against this user');
            $tranDtls = collect($tranDtls)->where('user_id', $userId)->whereNotNull('property_id');
            $propIds = collect($tranDtls)->pluck('property_id');
            $propDtls = PropProperty::whereIn('id', $propIds)->get();
            if ($propDtls->isEmpty())
                throw new Exception('No Property Found');
            $safIds = collect($propDtls)->pluck('saf_id');
            $geoTag = PropSafGeotagUpload::select('saf_id', 'latitude', 'longitude')
                ->whereIn('saf_id', $safIds)
                ->where('direction_type', 'ilike', '%front%')
                ->get();

            return responseMsgs(true, "tc Route", $geoTag, "010203", "1.0", responseTime(), 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", responseTime(), 'POST', "");
        }
    }

    /**
     * | quickAccessList
     */
    public function quickAccessList(Request $request)
    {
        try {
            $mQuickAccessMaster = new QuickAccessMaster();
            $list = $mQuickAccessMaster->getList();

            return responseMsgs(true, "quickAccessList",  $list, "010203", "1.0", responseTime(), 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", responseTime(), 'POST', "");
        }
    }

    /**
     * | quickAccessList
     */
    public function getQuickAccessListByUser(Request $request)
    {
        try {
            $userId = $request->userId ?? authUser()->id;
            $mQuickaccessUserMap = new QuickaccessUserMap();
            $list = $mQuickaccessUserMap->getListbyUserId($userId);

            return responseMsgs(true, "Quick Access List by user Id",  $list, "010203", "1.0", responseTime(), 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", responseTime(), 'POST', "");
        }
    }
}
