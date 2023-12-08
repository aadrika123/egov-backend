<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property\PropLocation;
use App\Models\Ulb\UlbNewWardmap;
use App\Repository\Ward\EloquentWardRepository;
use App\Http\Requests\Ward\UlbWardRequest;
use App\MicroServices\DocUpload;
use App\Models\Property\DataMap;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeLicence;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Converter\Time\UnixTimeConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

use function PHPSTORM_META\map;

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
     * | Map Level 
     */
    public function mapLevel1(Request $req)
    {
        $todayDate = Carbon::now()->format('Y-m-d');
        $data = DataMap::select('json')
            ->where('level', 1)
            ->where('date', $todayDate)
            ->first();

        if ($data)
            return responseMsgs(true, "Data Fetched", json_decode($data->json), "", "01", responseTime(), $req->getMethod(), $req->deviceId);


        // $data = UlbMaster::select(
        //     'ulb_masters.id as ulb_id',
        //     'ulb_name',
        //     'latitude',
        //     'longitude',
        //     'district_masters.district_code',
        //     'district_masters.district_name',
        //     DB::raw("count(prop_properties.id) as total_properties")
        // )
        //     ->join('district_masters', 'district_masters.id', 'ulb_masters.district_id')
        //     ->leftjoin('prop_properties', 'prop_properties.ulb_id', 'ulb_masters.id')
        //     ->groupBy(
        //         'ulb_masters.id',
        //         'ulb_name',
        //         'latitude',
        //         'longitude',
        //         'district_masters.district_code',
        //         'district_masters.district_name',
        //     )
        //     ->orderBy('ulb_name')
        //     ->get();

        $currentFy = getFY();
        $data = UlbMaster::select(
            'ulb_masters.id as ulb_id',
            'ulb_name',
            'latitude',
            'longitude',
            'district_masters.district_code',
            'district_masters.district_name',
            DB::raw("count(prop_properties.id) as total_properties"),
        )
            ->join('district_masters', 'district_masters.id', 'ulb_masters.district_id')
            ->leftjoin('prop_properties', 'prop_properties.ulb_id', 'ulb_masters.id')
            ->groupBy(
                'ulb_masters.id',
                'ulb_name',
                'latitude',
                'longitude',
                'district_masters.district_code',
                'district_masters.district_name',
            )
            ->orderBy('ulb_name')
            ->get();

        // return PropDemand::select(DB::raw('count(*) over () as paid_property'))
        //     ->where('fyear', $currentFy)
        //     ->where('ulb_id', 2)
        //     ->where('status', 1)
        //     ->groupBy('property_id')
        //     ->limit(1)
        //     ->first();

        // return $sql = 
        // DB::select("WITH property_counts AS (
        //                         SELECT COUNT(id) as total_properties
        //                         FROM prop_properties
        //                         WHERE ulb_id = 2
        //                         AND status = 1
        //                     ),
        //                     demand_counts AS (
        //                         SELECT COUNT(*) OVER () as total_demand
        //                         FROM prop_demands
        //                         WHERE fyear = '2023-2024'
        //                         AND ulb_id = 2
        //                         AND status = 1
        //                         GROUP BY property_id
        //                         LIMIT 1
        //                     ),
        //                     paid_counts AS (
        //                         SELECT COUNT(*) OVER () AS paid_property
        //                         FROM prop_demands
        //                         WHERE fyear = '2023-2024'
        //                         AND ulb_id = 2
        //                         AND paid_status = 1
        //                         AND status = 1
        //                         GROUP BY property_id
        //                         LIMIT 1
        //                     )
        //                     SELECT
        //                         (SELECT total_properties FROM property_counts) as total_properties,
        //                         (SELECT total_demand FROM demand_counts) as total_demand,
        //                         (SELECT paid_property FROM paid_counts) as paid_property;
        //                     ");

        $a = collect($data)->map(function ($data) use ($currentFy) {
            $count =  DB::select("WITH property_counts AS (
                                    SELECT COUNT(id) as total_properties
                                    FROM prop_properties
                                    WHERE ulb_id = $data->ulb_id
                                    AND status = 1
                                ),
                                demand_counts AS (
                                    SELECT COUNT(*) OVER () as total_demand
                                    FROM prop_demands
                                    WHERE fyear = '$currentFy'
                                    AND ulb_id = $data->ulb_id
                                    AND status = 1
                                    GROUP BY property_id
                                    LIMIT 1
                                ),
                                paid_counts AS (
                                    SELECT COUNT(*) OVER () AS paid_property
                                    FROM prop_demands
                                    WHERE fyear = '$currentFy'
                                    AND ulb_id = $data->ulb_id
                                    AND paid_status = 1
                                    AND status = 1
                                    GROUP BY property_id
                                    LIMIT 1
                                )
                                SELECT
                                    COALESCE((SELECT total_properties FROM property_counts),0) as total_properties,
                                    COALESCE((SELECT total_demand FROM demand_counts),0) as total_demand,
                                    COALESCE((SELECT paid_property FROM paid_counts),0) as paid_property;
                                ");
            $count = $count[0];
            $data['total_properties']   = $count->total_properties;
            $data['total_demand']       = $count->total_demand;
            $data['paid_property']      = $count->paid_property;
            $data['paid_percent']       = round((($count->paid_property > 0 ? ($count->paid_property) : 0) / ($count->total_demand > 0 ? $count->total_demand : 1)) * 100, 2);
            return $data;
        });

        $mDataMap = new DataMap();
        $mReqs = [
            "level" => 1,
            "date" => Carbon::now(),
            "json" => json_encode($data),
        ];
        $newData = $mDataMap->store($mReqs);

        return responseMsgs(true, "Data Fetched", $data, "", "01", responseTime(), $req->getMethod(), $req->deviceId);
    }
}
