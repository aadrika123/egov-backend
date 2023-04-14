<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            DB::raw("'active' as status"),
            // 'application_no' == null,
            'prop_properties.new_holding_no',
            'prop_active_deactivation_requests.property_id',
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

    /**
     * | REcent Applications
     */
    public function recentApplication($userId)
    {
        $data = PropActiveDeactivationRequest::select(
            'holding_no as holdingNo',
            'apply_date as applyDate',
            DB::raw(" 'Deactivation' as assessmentType"),
        )
            ->join('prop_properties', 'prop_properties.id', 'prop_active_deactivation_requests.property_id')
            ->where('prop_active_deactivation_requests.emp_detail_id', $userId)
            ->orderBydesc('prop_active_deactivation_requests.id')
            ->take(10)
            ->get();

        $application = collect($data)->map(function ($value) {
            $value['applyDate'] = (Carbon::parse($value['applyDate']))->format('d-m-Y');
            return $value;
        });
        return $application;
    }
}
