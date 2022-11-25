<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;
use App\Models\Property\PropActiveObjection;
use Illuminate\Support\Carbon;

/**
 * | Created On - 20-11-2022 
 * | Created By - Mrinal Kumar
 * | Created for the Objection Workflow Trait
 */
trait Objection
{

    // Get Concession List
    public function getObjectionList($ulbId)
    {
        return DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.id',
                'prop_active_objections.applicant_name as owner_name',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type',
                'prop_active_objections.workflow_id',
                'prop_active_objections.current_role as role_id'
            )
            ->leftJoin('prop_properties as a', 'a.id', '=', 'prop_active_objections.property_id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_objections.status', 1)
            ->where('prop_active_objections.ulb_id', $ulbId);
    }

    //insert data in Prop Active Objection

    public function postObjection($objection, $request)
    {
        $objectionTypeId = $request->id;
        $objection->property_id = $request->propertyId;
        $objection->objection_type_id = $objectionTypeId;
        $objection->objection_no = $this->_objectionNo;
        $objection->status = $request->status;
        $objection->remarks = $request->remarks;
        $objection->created_at = Carbon::now();
    }
}
