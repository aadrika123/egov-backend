<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;

/**
 * | Created On - 20-11-2022 
 * | Created By - Anshu Kumar
 * | Created for the Concession Workflow Trait
 */
trait Concession
{
    // Get Concession List
    public function getConcessionList($ulbId)
    {
        return DB::table('prop_active_concessions')
            ->select(
                'prop_active_concessions.id',
                'prop_active_concessions.applicant_name as owner_name',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type',
                'prop_active_concessions.workflow_id',
                'prop_active_concessions.current_role as role_id'
            )
            ->leftJoin('prop_safs as a', 'a.id', '=', 'prop_active_concessions.property_id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_concessions.status', 1)
            ->where('prop_active_concessions.ulb_id', $ulbId);
    }
}