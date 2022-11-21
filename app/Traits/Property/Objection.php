<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;

/**
 * | Created On - 20-11-2022 
 * | Created By - Mrinal Kumar
 * | Created for the Objection Workflow Trait
 */
trait Concession
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
}
