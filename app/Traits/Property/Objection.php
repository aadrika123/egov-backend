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
        return DB::table('prop_objections')
            ->select(
                '*'
            )
            ->leftJoin('safs as a', 'a.id', '=', 'prop_active_concessions.property_id')
            ->join('prop_m_property_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_concessions.status', 1)
            ->where('prop_active_concessions.ulb_id', $ulbId);
    }

    //insert data in Prop Active Objection

    public function postObjection($objection, $request)
    {
        $objectionType = $request->id;
        $objection->property_id = $request->propertyId;
        $objection->objection_type_id = $objectionType;
        $objection->objection_no = $this->_objectionNo;
        $objection->objection_form = $request->objectionForm;
        $objection->evidence_doc = $request->evidenceDoc;
        $objection->status = $request->status;
        $objection->remarks = $request->remarks;
        $objection->created_at = Carbon::now();
    }
}
