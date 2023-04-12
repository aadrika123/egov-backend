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
                'c.application_no',
                'c.applicant_name as owner_name',
                'p.new_holding_no',
                'pt_no',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'c.mobile_no'
            )
            ->join('prop_properties as p', 'p.id', '=', 'c.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('c.application_no', strtoupper($concessionNo))
            ->first();
    }
}
