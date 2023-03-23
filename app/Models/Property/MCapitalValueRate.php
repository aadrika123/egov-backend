<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MCapitalValueRate extends Model
{
    use HasFactory;


    /**
     * | Get Capital Value Rate 
     */
    public function getCVRate($req)
    {
        return MCapitalValueRate::where('ward_no', $req->wardNo)
            ->select('rate', 'max_rate')
            ->where('property_type', $req->propertyType)
            ->where('road_type_mstr_id', $req->roadTypeMstrId)
            ->where('usage_type', $req->usageType)
            ->where('ulb_id', $req->ulbId)
            ->where('status', 1)
            ->first();
    }
}
