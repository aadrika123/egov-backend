<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MCapitalValueRate extends Model
{
    use HasFactory;


    /**
     * | Get Capital Value Rate 
     */
    public function getCVRate($req)
    {
        return MCapitalValueRate::where('ward_no', $req->wardNo)
            ->select('rate', 'max_rate', 'effect_from')
            ->where('property_type', $req->propertyType)
            ->where('road_type_mstr_id', $req->roadTypeMstrId)
            ->where('usage_type', $req->usageType)
            ->where('ulb_id', $req->ulbId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get CV Rate By Ward No 
     */
    public function readCvRatesByWardNo($wardNo)
    {
        $capitalValueRates = json_decode(Redis::get('cv_by_ward_no_' . $wardNo));
        if (!$capitalValueRates) {
            $capitalValueRates = MCapitalValueRate::where('ward_no', $wardNo)
                ->select(
                    "*",
                    DB::raw("
            case when road_type_mstr_id = '1' then 'Main Road' else 'Others' end as road_type")
                )
                ->get();
            Redis::set('cv_by_ward_no' . $wardNo, json_encode($capitalValueRates));
        }
        return $capitalValueRates;
    }

    //written by prity pandey

    public function getById($req)
    {
        $list = MCapitalValueRate::select(
            'id',
            'property_type',
            'ward_no',
            'road_type_mstr_id',
            'usage_type',
            'rate',
            'effect_from',
            'max_rate',
            'ulb_id',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listCapitalValueRate()
    {
        $list = MCapitalValueRate::select(
            'id',
            'property_type',
            'ward_no',
            'road_type_mstr_id',
            'usage_type',
            'rate',
            'effect_from',
            'max_rate',
            'ulb_id',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
