<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;

/**
 * | Created On-17-10-2022 
 * | Created By - Anshu Kumar
 * | Created for - Code Reausable for SAF Repository
 */

trait SAF
{
    // SAF Inbox 
    public function getSaf()
    {
        $data = DB::table('active_saf_details')
            ->join('active_saf_owner_details as o', 'o.saf_dtl_id', '=', 'active_saf_details.id')
            ->join('prop_param_property_types as p', 'p.id', '=', 'active_saf_details.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'active_saf_details.ward_mstr_id')
            ->select(
                'active_saf_details.saf_no',
                'active_saf_details.id',
                'active_saf_details.ward_mstr_id',
                'ward.ward_name as ward_no',
                'active_saf_details.prop_type_mstr_id',
                'active_saf_details.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                'p.property_type',
                'active_saf_details.assessment_type'
            );
        return $data;
    }
}
