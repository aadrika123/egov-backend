<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveDeactivationRequest extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * | Get details of deactivation list by holding no
     */
    public function getDeactivationApplication($holdingNo)
    {
        return PropActiveDeactivationRequest::select(
            'prop_active_deactivation_requests.id',
            // 'application_no' == null,
            'prop_properties.new_holding_no',
            'prop_active_deactivation_requests.id as property_id',
            'prop_properties.ward_mstr_id',
            'prop_properties.new_ward_mstr_id',
            'u.ward_name as old_ward_no',
            'u1.ward_name as new_ward_no'
        )
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_active_deactivation_requests.property_id')
            ->join('ulb_ward_masters as u', 'prop_properties.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'prop_properties.new_ward_mstr_id', '=', 'u1.id')
            ->where('prop_properties.holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->first();
    }
}
