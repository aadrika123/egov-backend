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
            ->orWhere('new_holding_no', $holdingNo)
            ->select('id')
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
                'r.road_type',
                'a.apartment_name',
                'a.apt_code as apartment_code'
            )
            ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_properties.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'prop_properties.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 'prop_properties.road_type_mstr_id')
            ->leftJoin('prop_apartment_dtls as a', 'a.id', '=', 'prop_properties.apartment_details_id')
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

    /**
     * | Get Proprty Details By Holding No
     */
    public function getPropByHolding($holdingNo, $ulbId)
    {
        $oldHolding = PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'prop_properties.new_holding_no',
            'prop_properties.ward_mstr_id',
            'prop_properties.new_ward_mstr_id',
            'prop_properties.elect_consumer_no',
            'prop_properties.elect_acc_no',
            'prop_properties.elect_bind_book_no',
            'prop_properties.elect_cons_category',
            'prop_properties.prop_pin_code',
            'prop_properties.corr_pin_code',
            'prop_properties.prop_address',
            'prop_properties.corr_address',
            'prop_properties.area_of_plot as total_area_in_desimal',
            'ulb_ward_masters.ward_name as old_ward_no',
            'u.ward_name as new_ward_no',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 'prop_properties.new_ward_mstr_id')
            ->where('prop_properties.holding_no', $holdingNo)
            ->where('prop_properties.ulb_id', $ulbId)
            ->first();

        if ($oldHolding) {
            return $oldHolding;
        }

        $newHolding = PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'prop_properties.new_holding_no',
            'prop_properties.ward_mstr_id',
            'prop_properties.new_ward_mstr_id',
            'prop_properties.elect_consumer_no',
            'prop_properties.elect_acc_no',
            'prop_properties.elect_bind_book_no',
            'prop_properties.elect_cons_category',
            'prop_properties.prop_pin_code',
            'prop_properties.corr_pin_code',
            'prop_properties.prop_address',
            'prop_properties.corr_address',
            'prop_properties.area_of_plot as total_area_in_desimal',
            'ulb_ward_masters.ward_name as old_ward_no',
            'u.ward_name as new_ward_no',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 'prop_properties.new_ward_mstr_id')
            ->orwhere('prop_properties.new_holding_no', $holdingNo)
            ->where('prop_properties.ulb_id', $ulbId)
            ->first();
        return $newHolding;
    }

    /**
     * | get property details by userId and ulbId
     */
    public function getpropByUserUlb($request)
    {
        return PropProperty::select(
            'new_holding_no',
            'holding_no'
        )
            ->where('user_id', auth()->user()->id)
            // ->orWhere('citizen_id', auth()->user()->id)
            ->where('ulb_id', $request->ulbId)
            ->get();
    }
}
