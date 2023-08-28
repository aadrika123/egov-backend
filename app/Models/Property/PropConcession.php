<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropConcession extends Model
{
    use HasFactory;

    /**
     * | Get Concession Application Dtls by application No
     */
    public function getDtlsByConcessionNo($concessionNo)
    {
        return DB::table('prop_concessions as c')
            ->select(
                'c.id',
                DB::raw("'approved' as status"),
                'c.application_no',
                'c.applicant_name as owner_name',
                'p.new_holding_no',
                'pt_no',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'role_name as currentRole',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'c.mobile_no'
            )
            ->leftjoin('wf_roles', 'wf_roles.id', 'c.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'c.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('c.application_no', strtoupper($concessionNo))
            ->first();
    }

    /**
     * | Search Concessions
     */
    public function searchConcessions()
    {
        return PropConcession::on('pgsql::read')
            ->select(
                'prop_concessions.id',
                DB::raw("'approved' as status"),
                'prop_concessions.application_no',
                'prop_concessions.current_role',
                'role_name as currentRole',
                'u.ward_name as old_ward_no',
                'uu.ward_name as new_ward_no',
                'prop_address',
                'owner_name',
                'prop_owners.mobile_no',
                'new_holding_no',
                'pp.id as property_id'
            )

            ->leftjoin('wf_roles', 'wf_roles.id', 'prop_concessions.current_role')
            ->join('prop_properties as pp', 'pp.id', 'prop_concessions.property_id')
            ->join('ulb_ward_masters as u', 'u.id', 'pp.ward_mstr_id')
            ->join('ulb_ward_masters as uu', 'uu.id', 'pp.new_ward_mstr_id')
            ->join('prop_owners', 'prop_owners.property_id', 'prop_concessions.prop_owner_id');
    }
}
