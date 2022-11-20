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
            ->select('prop_active_concessions.*', 'a.ward_mstr_id')
            ->leftJoin('safs as a', 'a.id', '=', 'prop_active_concessions.property_id')
            ->where('prop_active_concessions.status', 1)
            ->where('prop_active_concessions.ulb_id', $ulbId);
    }
}
