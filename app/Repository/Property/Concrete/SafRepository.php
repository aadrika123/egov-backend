<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Support\Facades\DB;

/**
 * | Repository for SAF
 */
class SafRepository implements iSafRepository
{
    /**
     * | Get Saf Details
     */
    public function getSaf($workflowIds)
    {
        $data = DB::table('prop_active_safs')
            ->leftJoin('prop_active_safs_owners as o', 'o.saf_id', '=', 'prop_active_safs.id')
            ->join('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
            ->select(
                'prop_active_safs.saf_no',
                'prop_active_safs.id',
                'prop_active_safs.ward_mstr_id',
                'ward.ward_name as ward_no',
                'prop_active_safs.prop_type_mstr_id',
                'prop_active_safs.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                DB::raw("string_agg(o.mobile_no,',') as mobile_no"),
                'p.property_type',
                'prop_active_safs.assessment_type as assessment',
                'prop_active_safs.application_date as apply_date',
                'prop_active_safs.parked',
                'prop_active_safs.prop_address',
                'prop_active_safs.applicant_name',
            )
            ->whereIn('workflow_id', $workflowIds)
            ->where('is_gb_saf', false)
            ->where('payment_status', 1);
        return $data;
    }
}
