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
            ->select(
                'prop_properties.*',
                'prop_properties.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'o.ownership_type',
                'ref_prop_types.property_type',
                'r.road_type'
            )
            ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_properties.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 'prop_properties.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->join('ref_prop_road_types as r', 'r.id', '=', 'prop_properties.road_type_mstr_id')
            ->where('prop_properties.status', 1);
    }

    /**
     * | Get Property Full Details
     * | Used for Calculation Parameter
     * | @param propId Property Id
     */
    public function getPropFullDtls($propId)
    {
        $mPropOwners = new PropOwner();
        $mPropFloors = new PropFloor();
        $details = array();
        $details = PropProperty::find($propId);
        $owners = $mPropOwners->getOwnersByPropId($propId);
        $details['owners'] = $owners;
        $floors = $mPropFloors->getFloorsByPropId($propId);
        $details['floors'] = $floors;
        return $details;
    }

    /**
     * | Get Property Details
     */
    public function getPropByHoldingNo($holdingNo)
    {
        return PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'ward_name',
            'prop_address'
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
            ->where('prop_properties.holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->get();
    }
}
