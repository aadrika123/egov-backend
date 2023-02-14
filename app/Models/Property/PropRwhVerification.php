<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropRwhVerification extends Model
{
    use HasFactory;

    /**
     * 
     */
    public function getVerificationsData($applicationId)
    {
        return DB::table('prop_rwh_verifications')
            ->select(
                'prop_rwh_verifications.*',
                // 'p.property_type',
                // 'r.road_type',
                // 'u.ward_name as ward_no'
            )
            // ->join('ulb_ward_masters as u', 'u.id', '=', 'prop_saf_verifications.ward_id')
            ->where('prop_rwh_verifications.harvesting_id', $applicationId)
            ->where('prop_rwh_verifications.agency_verification', true)
            ->first();
    }
}
