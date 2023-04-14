<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSaf extends Model
{
    use HasFactory;

    /**
     * | Get citizen safs
     */
    public function getCitizenSafs($citizenId, $ulbId)
    {
        return PropSaf::select('id', 'saf_no', 'citizen_id')
            ->where('citizen_id', $citizenId)
            ->where('ulb_id', $ulbId)
            ->get();
    }

    /**
     * | 
     */
    public function getSafDtlsBySafNo($safNo)
    {
        return DB::table('prop_safs as s')
            ->where('s.saf_no', strtoupper($safNo))
            ->select(
                's.id',
                DB::raw("'approved' as status"),
                's.saf_no',
                's.ward_mstr_id',
                's.new_ward_mstr_id',
                's.elect_consumer_no',
                's.elect_acc_no',
                's.elect_bind_book_no',
                's.elect_cons_category',
                's.prop_address',
                's.corr_address',
                's.prop_pin_code',
                's.corr_pin_code',
                's.assessment_type',
                's.applicant_name',
                's.application_date',
                's.area_of_plot as total_area_in_decimal',
                's.prop_type_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'p.property_type',
                'doc_upload_status',
                'payment_status',
                'role_name as currentRole',
                'role_name as approvedBy',
                's.user_id',
                's.citizen_id',
                DB::raw(
                    "case when s.user_id is not null then 'TC/TL/JSK' when 
                    s.citizen_id is not null then 'Citizen' end as appliedBy
                "
                ),
            )
            ->leftjoin('wf_roles', 'wf_roles.id', 's.current_role')
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->join('ref_prop_types as p', 'p.id', '=', 's.prop_type_mstr_id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->first();
    }

    /**
     * | Get GB SAf details by saf No
     */
    public function getGbSafDtlsBySafNo($safNo)
    {
        return DB::table('prop_safs as s')
            ->where('s.saf_no', strtoupper($safNo))
            ->select(
                's.id',
                DB::raw("'approved' as status"),
                's.saf_no',
                's.ward_mstr_id',
                's.new_ward_mstr_id',
                's.prop_address',
                's.prop_pin_code',
                's.assessment_type',
                's.applicant_name',
                's.application_date',
                's.area_of_plot as total_area_in_decimal',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'doc_upload_status',
                'payment_status',
                // 'role_name as currentRole',
                'role_name as approvedBy',
                's.user_id',
                's.citizen_id',
                'gb_office_name',
                'building_type',
                DB::raw(
                    "case when s.user_id is not null then 'TC/TL/JSK' when 
                    s.citizen_id is not null then 'Citizen' end as appliedBy
                "
                ),
            )
            ->join('wf_roles', 'wf_roles.id', 's.current_role')
            ->leftjoin('ref_prop_gbpropusagetypes as p', 'p.id', '=', 's.gb_usage_types')
            ->leftjoin('ref_prop_gbbuildingusagetypes as q', 'q.id', '=', 's.gb_prop_usage_types')
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->first();
    }

    /**
     * | Search safs
     */
    public function searchSafs()
    {
        return PropSaf::select(
            'prop_safs.id',
            DB::raw("'approved' as status"),
            'prop_safs.saf_no',
            'prop_safs.assessment_type',
            'prop_safs.current_role',
            'role_name as currentRole',
            'ward_name',
            'prop_address',
            DB::raw("string_agg(so.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(so.owner_name,',') as owner_name"),
        )
            ->leftjoin('wf_roles', 'wf_roles.id', 'prop_safs.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_safs.ward_mstr_id')
            ->join('prop_safs_owners as so', 'so.saf_id', 'prop_safs.id');
    }

    /**
     * | Search Gb Saf
     */
    public function searchGbSafs()
    {
        return PropSaf::select(
            'prop_safs.id',
            DB::raw("'approved' as status"),
            'prop_safs.saf_no',
            'prop_safs.assessment_type',
            'prop_safs.current_role',
            'role_name as currentRole',
            'ward_name',
            'prop_address',
            'gbo.officer_name',
            'gbo.mobile_no'
        )
            ->leftjoin('wf_roles', 'wf_roles.id', 'prop_safs.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_safs.ward_mstr_id')
            ->join('prop_gbofficers as gbo', 'gbo.saf_id', 'prop_safs.id');
    }
}
