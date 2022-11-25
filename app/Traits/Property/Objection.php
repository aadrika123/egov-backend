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
        return DB::table('prop_active_objections');
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
