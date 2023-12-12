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
                return responseMsgs(false, "ULB ID does not exist", "", "012501", 1.0, "308ms", "POST", $request->deviceId);
            }

            $locations = PropLocation::where('ulb_id', $ulbId)->where("status", 1)->select('id', 'location')->get();

            return responseMsgs(true, "Location Details", $locations, "012501", 1.0, "308ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012501", 1.0, "308ms", "POST", $request->deviceId);
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
            return responseMsgs(true, "Wards Bound to Location", $wards, "012502", 1.0, "308ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012502", 1.0, "308ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Map Level 1
     */
    public function mapLevel1(Request $req)
    {
        $currentFy = getFY();
        $todayDate = Carbon::now()->format('Y-m-d');

        $data = DataMap::select('json')
            ->where('level', 1)
            ->where('date', $todayDate)
            ->first();

        if ($data)
            return responseMsgs(true, "Data Fetched", json_decode($data->json), "", "01", responseTime(), $req->getMethod(), $req->deviceId);

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

        // return $data = DB::select("SELECT u.ulb_name,d.*
        //                                 from ulb_masters u
        //                                 left join (
        //                                     SELECT 
        //                                     count(a.prop_count),
        //                                     sum(a.prop_demand_cnt),
        //                                     a.ulb_id

        //                                     FROM (
        //                                         select  
        //                                             p.id as prop_count,
        //                                             count(d.id) as prop_demand_cnt,
        //                                             p.ulb_id

        //                                         from prop_properties p
        //                                         join prop_demands d on d.property_id=p.id and d.status=1 and d.fyear='2023-2024'
        //                                         where p.status=1
        //                                         group by p.id
        //                                     ) as a

        //                                     group by a.ulb_id
        //                                 ) d on d.ulb_id=u.id");

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

        return responseMsgs(true, "Data Fetched", $data, "012503", "01", responseTime(), $req->getMethod(), $req->deviceId);
    }

    /**
     * | Map Level 2
     */
    public function mapLevel2(Request $req)
    {
        $currentFy = getFY();
        $todayDate = Carbon::now()->format('Y-m-d');
        $mUlbWardMaster = new UlbWardMaster();

        // $data = DataMap::select('json')
        //     ->where('level', 2)
        //     ->where('date', $todayDate)
        //     ->first();

        // if ($data)
        //     return responseMsgs(true, "Data Fetched", json_decode($data->json), "", "01", responseTime(), $req->getMethod(), $req->deviceId);

        $wards = $mUlbWardMaster->getWardByUlbId($req->ulbId);
        $wards = collect($wards)->sortBy('id')->values();
        // $wards = collect($wards)->sortBy('ward_name')->values();
        // $wardIds = collect($wards)->pluck('id');

        // $latlong = PropProperty::select('prop_properties.id', 'ward_mstr_id', 'latitude', 'longitude')
        //     ->join('prop_saf_geotag_uploads', 'prop_saf_geotag_uploads.saf_id', 'prop_properties.saf_id')
        //     ->distinct('ward_mstr_id')
        //     ->whereIn('ward_mstr_id', $wardIds)
        //     ->get();

        // collect($latlong)->map(function ($latlong) {

        //     UlbWardMaster::where('id', $latlong->ward_mstr_id)
        //         ->update([
        //             'latitude' => $latlong->latitude,
        //             'longitude' => $latlong->longitude,
        //         ]);
        // });
        // die("ok");


        return $data = collect($wards)->map(function ($wards) use ($currentFy) {
            $count =  DB::select("WITH property_counts AS (
                                    SELECT COUNT(id) as total_properties
                                    FROM prop_properties
                                    WHERE ward_mstr_id = $wards->id
                                    AND status = 1
                                ),
                                demand_counts AS (
                                    SELECT COUNT(*) OVER () as total_demand
                                    FROM prop_demands   
                                    WHERE fyear = '$currentFy'
                                    AND ward_mstr_id = $wards->id
                                    AND status = 1
                                    GROUP BY property_id
                                    LIMIT 1
                                ),
                                paid_counts AS (
                                    SELECT COUNT(*) OVER () AS paid_property
                                    FROM prop_demands
                                    WHERE fyear = '$currentFy'
                                    AND ward_mstr_id = $wards->id
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
            $wards['total_properties']   = $count->total_properties;
            $wards['total_demand']       = $count->total_demand;
            $wards['paid_property']      = $count->paid_property;
            $wards['paid_percent']       = round((($count->paid_property > 0 ? ($count->paid_property) : 0) / ($count->total_demand > 0 ? $count->total_demand : 1)) * 100, 2);
            return $wards;
        });
        return $wards;

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
