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
        try{ 
            $ulbId = $request->ulbId;
            $ulbExists = PropLocation::where('ulb_id', $ulbId)->exists();

            if (!$ulbExists) {
            return responseMsgs(false, "ULB ID does not exist", "", 010124, 1.0, "308ms", "POST", $request->deviceId);
        }
        
            $locations = PropLocation::where('ulb_id', $ulbId)->where("status",1)->select('id', 'location')->get();

            return responseMsgs(true, "Location Details", $locations, 010124, 1.0, "308ms", "POST", $request->deviceId);
    } 
        catch (Exception $e) {
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
        ->join('location_ward_maps', function($join) {
            $join->on('location_ward_maps.ward_id', '=', 'ulb_ward_masters.id');
                //->whereColumn('location_ward_maps.ulb_id', '=', 'ulb_ward_masters.ulb_id');
        })
        ->join('prop_locations', 'prop_locations.id', '=', 'location_ward_maps.location_id')
        ->where('prop_locations.ulb_id', $ulbId)
        ->where('ulb_ward_masters.status', 1)
        ->orderBy('ward_name')
        ->get();
        return responseMsgs(true, "Wards Bound to Location", $wards, 010124, 1.0, "308ms", "POST", $request->deviceId);
    }
    catch (Exception $e) {
        return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $request->deviceId);
    }
}

    /**
     * Citizen details by citizen_id|
     * written by Prity pandey
     * 
     */
    public function citizen_details_with_citizen_id(Request $request)
    { 
        try {
            $citizen_id = Auth()->user()->id;
           $prop = PropProperty::select("prop_properties.holding_no",'prop_properties.new_holding_no',"prop_properties.application_date as holding_date","prop_properties.id","prop_properties.prop_address as property_address","prop_properties.saf_id","prop_properties.road_width","prop_properties.area_of_plot","ulb_ward_masters.old_ward_name as ward_no","new_ward_no.ward_name as new_ward_no",'o.ownership_type','ref_prop_types.property_type')

            ->leftjoin("ulb_ward_masters","ulb_ward_masters.id","prop_properties.ward_mstr_id")
            ->leftjoin("ulb_ward_masters as new_ward_no","new_ward_no.id","prop_properties.new_ward_mstr_id")
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'prop_properties.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
          ->join("prop_owners","prop_owners.property_id","prop_properties.id")
           ->where('prop_properties.citizen_id', $citizen_id)
           ->where("prop_properties.status",1)
           ->get();

           
           $data["property"] = $prop->map(function($val){
            $val->owners = $val->owners()->get();
            $val->floors = $val->floors()->get();
            
            $val->geotags = $val->geotags()->get()->map(function($img){
                $docUrl = Config::get('module-constants.DOC_URL');
                $img->image_path = ($img->image_path || $img->relative_path) ? ($docUrl."/".$img->relative_path."/".$img->image_path) : "";
                return $img;
            });
            $demand = $val->demands()->get();
            $lastTran = $val->lastTransection()->first();
            $val->demands = [
                "is_due" => $demand->sum("balance")>0?true:false,
                "amount" => $demand->sum("balance"),
                "paid_date" => ($lastTran->tran_date??false)? Carbon::parse($lastTran->tran_date)->format("d-m-Y"):"",
            ];
            $val->water = $val->waterConsumer()->get()->map(function($consumer){               
                $demand = (new \App\Models\Water\WaterConsumerDemand)->getConsumerDemand($consumer->id);                
                
                $lastTran = $consumer->lastTran()->first();
                $consumer->is_due = $demand->sum("balance")>0?true:false;
                $consumer->amount = $demand->sum("balance");
                $consumer->paid_date = ($lastTran->tran_date??false)? Carbon::parse($lastTran->tran_date)->format("d-m-Y"):"";
                return $consumer;
            });

            $val->trade = $val->tradeConnection()->get();
            return $val;
           });
    
           return responseMsgs(true, "property details", $data, 010124, 1.0, "308ms", "POST", $request->deviceId);
        }


        catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $request->deviceId);
        }
    }
}
