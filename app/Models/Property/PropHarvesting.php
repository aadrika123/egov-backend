<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropHarvesting extends Model
{
    use HasFactory;

    /**
     * | Get Harvesting By Harvesting No
     */
    public function getDtlsByHarvestingNo($harvestingNo)
    {
        return DB::table('prop_harvestings as h')
            ->select(
                'h.id',
                'h.application_no',
                'holding_no',
                'p.new_holding_no',
                'pt_no',
                'pt.property_type',
                'p.id as property_id',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as ward_no',
                'u1.ward_name as new_ward_no',
                'h.date'
            )
            ->join('prop_properties as p', 'p.id', '=', 'h.property_id')
            ->leftjoin('ref_prop_types as pt', 'pt.id', '=', 'p.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('application_no', strtoupper($harvestingNo))
            ->first();
    }
}
