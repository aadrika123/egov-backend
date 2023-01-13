<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;

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
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_no as application_no',
                'p.ward_mstr_id as old_ward_id',
                'u.ward_name as old_ward_no',
                'p.new_ward_mstr_id',
                'p.applicant_name',
                'p.new_holding_no',
                'p.application_date',
                'p.balance',
                't.property_type',
                'p.assessment_type',
                'objection_for'
            )
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->leftJoin('ref_prop_types as t', 't.id', '=', 'p.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'p.ward_mstr_id')
            // ->leftJoin('ulb_ward_masters as u1', 'u.id', '=', 'p.new_ward_mstr_id')
            ->where('prop_active_objections.ulb_id', $ulbId);
    }

    //insert data in Prop Active Objection


}
