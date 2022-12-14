<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSafVerification extends Model
{
    use HasFactory;

    public function getVerificationsData($safId)
    {
        return DB::table('prop_saf_verifications')
            ->select('prop_saf_verifications.*', 'p.property_type', 'r.road_type', 'u.ward_name as ward_no')
            ->join('ref_prop_types as p', 'p.id', '=', 'prop_saf_verifications.prop_type_id')
            ->join('ref_prop_road_types as r', 'r.id', '=', 'prop_saf_verifications.road_type_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'prop_saf_verifications.ward_id')
            ->where('prop_saf_verifications.saf_id', $safId)
            ->where('prop_saf_verifications.agency_verification', true)
            ->first();
    }
}
