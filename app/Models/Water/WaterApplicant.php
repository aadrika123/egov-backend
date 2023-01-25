<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApplicant extends Model
{
    use HasFactory;

    /**
     * |--------------------------- save new water applicants details ------------------------------|
     * | @param
     * |
        | Apply City and District
     */
    public function saveWaterApplicant($applicationId, $owners)
    {
        $applicant = new WaterApplicant();
        $applicant->application_id  = $applicationId;
        $applicant->applicant_name  = $owners['ownerName'];
        $applicant->guardian_name   = $owners['guardianName'] ?? null;
        $applicant->mobile_no       = $owners['mobileNo'];
        $applicant->email           = $owners['email'];
        $applicant->save();
    }

    /**
     * |----------------------------------- Owner Detail By ApplicationId / active applications ----------------------------|
     * | @param request
     */
    public function ownerByApplication($request)
    {
        return WaterApplicant::select(
            'water_applicants.applicant_name as owner_name',
            'guardian_name',
            'mobile_no',
            'email',
            'city',
            'district'
        )
            ->join('water_applications', 'water_applications.id', '=', 'water_applicants.application_id')
            ->where('water_applications.id', $request->applicationId)
            ->where('water_applications.status', 1)
            ->where('water_applicants.status', 1);
    }

    /**
     * |
     */
    public function getOwnerList($applicationId)
    {
        return WaterApplicant::select(
            'applicant_name',
            'guardian_name',
            'mobile_no',
            'email'
        )
            ->where('application_id', $applicationId);
    }

    /**
     * |-------------- Delete the applicant -------------|
     */
    public function deleteWaterApplicant($id)
    {
        WaterApplicant::where('application_id',$id)
        ->delete();
    }

    /**
     * |---------- Edit the water owner Details ----------|
     */
    public function editWaterOwners($req,$refWaterApplications)
    {
            $owner = WaterApplicant::find($req->ownerId);
            $reqs = [
                'applicant_name'  =>$req->applicant_name ?? $refWaterApplications->applicant_name,
                'guardian_name'   =>$req->guardian_name  ?? $refWaterApplications->guardian_name,
                'mobile_no'       =>$req->mobile_no      ?? $refWaterApplications->mobile_no,
                'email'           =>$req->email          ?? $refWaterApplications->email,
            ];
            $owner->update($reqs);
    }
}
