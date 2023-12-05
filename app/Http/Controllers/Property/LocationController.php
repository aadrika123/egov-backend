<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property\PropLocation;
use App\Models\Ulb\UlbNewWardmap;
use App\Repository\Ward\EloquentWardRepository;
use App\Http\Requests\Ward\UlbWardRequest;
use App\MicroServices\DocUpload;
use App\Models\Property\PropProperty;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeLicence;
use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Converter\Time\UnixTimeConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class LocationController extends Controller
{
    /**
     * | ULB location list
     * * written by Prity pandey
     */
    public function location_list(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            ['ulbId' => 'required|integer']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }
        try {
            $ulbId = $request->ulbId;
            $ulbExists = PropLocation::where('ulb_id', $ulbId)->exists();

            if (!$ulbExists) {
                return responseMsgs(false, "ULB ID does not exist", "", 010124, 1.0, "308ms", "POST", $request->deviceId);
            }

            $locations = PropLocation::where('ulb_id', $ulbId)->where("status", 1)->select('id', 'location')->get();

            return responseMsgs(true, "Location Details", $locations, 010124, 1.0, "308ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $request->deviceId);
        }
    }


    /**
     * |ULB ward list
     * * written by Prity pandey
     */
    public function bindLocationWithWards(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            ['ulbId' => 'required|integer']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }
        try {
            $ulbId = $request->ulbId;
            DB::enableQueryLog();
            $wards = UlbWardMaster::select(
                'ulb_ward_masters.id as location_id',
                'ulb_ward_masters.id as id',
                'ulb_ward_masters.old_ward_name as ward_name',
                'new_ward.old_ward_mstr_id as new_ward_id',
                'ulb_ward_masters.ward_name as new_ward_name'
            )
                ->join('ulb_new_wardmaps', 'ulb_new_wardmaps.old_ward_mstr_id', '=', 'ulb_ward_masters.id')
                ->join('ulb_new_wardmaps as new_ward', 'new_ward.new_ward_mstr_id', '=', 'ulb_ward_masters.id')
                ->join('location_ward_maps', function ($join) {
                    $join->on('location_ward_maps.ward_id', '=', 'ulb_ward_masters.id');
                    //->whereColumn('location_ward_maps.ulb_id', '=', 'ulb_ward_masters.ulb_id');
                })
                ->join('prop_locations', 'prop_locations.id', '=', 'location_ward_maps.location_id')
                ->where('prop_locations.ulb_id', $ulbId)
                ->where('ulb_ward_masters.status', 1)
                ->orderBy('ward_name')
                ->get();
            return responseMsgs(true, "Wards Bound to Location", $wards, 010124, 1.0, "308ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $request->deviceId);
        }
    }

    /**
     * | 
     */
}
