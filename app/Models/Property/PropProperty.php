<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropProperty extends Model
{
    use HasFactory;

    // Get Property Of the Citizen
    public function getUserProperties($userId)
    {
        return PropProperty::where('user_id', $userId)
            ->get();
    }

    // get Prpoperty id
    public function getPropertyId($holdingNo)
    {
        return PropProperty::where('holding_no', $holdingNo)
            ->select('id')
            ->get()
            ->first();
    }

    /**
     * | Get Property Details
     */
    public function getPropDtls()
    {
        return DB::table('prop_properties')
            ->select('s.*', 's.assessment_type as assessment', 'w.ward_name as old_ward_no', 'o.ownership_type', 'p.property_type', 'r.road_type')
            ->join('prop_safs as s', 's.id', '=', 'prop_properties.saf_id')
            ->join('ulb_ward_masters as w', 'w.id', '=', 's.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 's.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 's.property_assessment_id')
            ->join('ref_prop_road_types as r', 'r.id', '=', 'prop_properties.road_type_mstr_id')
            ->where('prop_properties.status', 1);
    }
}
