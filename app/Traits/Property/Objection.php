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
            ->select('prop_active_objections.id', 'p.applicant_name', 'p.new_holding_no', 'p.application_date', 'p.balance', 't.property_type')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->leftJoin('ref_prop_types as t', 't.id', '=', 'p.prop_type_mstr_id')
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
