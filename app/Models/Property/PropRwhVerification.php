<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropRwhVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'harvesting_id',
        'harvesting_status',
        'agency_verification',
        'ulb_verification',
        'date',
        'user_id',
        'ulb_id',
    ];

    /**
     * | Get RWH Verifications Data by Application ID
       | Reference Function : getTcVerifications
     */
    public function getVerificationsData($applicationId)
    {
        return PropRwhVerification::on('pgsql::read')
            ->select(
                '*',
                'prop_rwh_verifications.harvesting_status',
                'agency_verification'
            )
            ->where('prop_rwh_verifications.harvesting_id', $applicationId)
            ->where('prop_rwh_verifications.agency_verification', true)
            ->first();
    }

    /**
     * | Store RWH Verification Data
       | Reference Function : siteVerification
     */
    public function store($req)
    {
        $metaReqs = [
            'property_id' => $req->propertyId,
            'harvesting_id' => $req->harvestingId,
            'harvesting_status' => $req->harvestingStatus,
            'agency_verification' => $req->agencyVerification ?? null,
            'ulb_verification' => $req->ulbVerification ?? null,
            'date' => Carbon::now(),
            'user_id' => $req->userId,
            'ulb_id' => $req->ulbId
        ];

        return PropRwhVerification::create($metaReqs)->id;
    }
}
