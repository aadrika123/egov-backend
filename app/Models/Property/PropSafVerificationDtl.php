<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSafVerificationDtl extends Model
{
    use HasFactory;

    // Get Floor Details by Verification Id
    public function getVerificationDetails($verificationId)
    {
        return PropSafVerificationDtl::where('verification_id', $verificationId)->get();
    }

    // Get Full Verification Details
    public function getFullVerificationDtls($verifyId)
    {
        return DB::table('prop_saf_verification_dtls')
            ->select('prop_saf_verification_dtls.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
            ->join('ref_prop_floors as f', 'f.id', '=', 'prop_saf_verification_dtls.floor_mstr_id')
            ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_saf_verification_dtls.usage_type_id')
            ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_saf_verification_dtls.occupancy_type_id')
            ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_saf_verification_dtls.construction_type_id')
            ->where('verification_id', $verifyId)
            ->get();
    }
}
