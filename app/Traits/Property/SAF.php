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
        $data = DB::table('active_safs')
            ->join('active_safs_owner_dtls as o', 'o.saf_id', '=', 'active_safs.id')
            ->join('prop_m_property_types as p', 'p.id', '=', 'active_safs.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'active_safs.ward_mstr_id')
            ->select(
                'active_safs.saf_no',
                'active_safs.id',
                'active_safs.ward_mstr_id',
                'ward.ward_name as ward_no',
                'active_safs.prop_type_mstr_id',
                'active_safs.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                'p.property_type',
                'active_safs.assessment_type'
            );
        return $data;
    }
}
