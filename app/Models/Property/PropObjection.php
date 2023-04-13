<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropObjection extends Model
{
    use HasFactory;

    /**
     * | Get Objection by Objection No
     */
    public function getObjByObjNo($objectionNo)
    {
        return DB::table('prop_objections as o')
            ->select(
                'o.id',
                'o.objection_no as application_no',
                'p.new_holding_no',
                'p.id as property_id',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no'
            )
            ->join('prop_properties as p', 'p.id', '=', 'o.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('o.objection_no', strtoupper($objectionNo))
            ->first();
    }
}
