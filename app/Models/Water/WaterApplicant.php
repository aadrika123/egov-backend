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
        |
     */
    public function saveWaterApplicant($applicationId, $owners)
    {
        $applicant = new WaterApplicant();
        $applicant->application_id = $applicationId;
        $applicant->applicant_name = $owners['ownerName'];
        $applicant->guardian_name = $owners['guardianName'];
        $applicant->mobile_no = $owners['mobileNo'];
        $applicant->email = $owners['email'];
        $applicant->save();
    }

    /**
     * |----------------------------------- Owner Detail By ApplicationId / active applications ----------------------------|
     * | @param request
     */
    public function ownerByApplication($request)
    {
        return WaterApplicant::join('water_applications', 'water_applications.id', '=', 'water_applicants.application_id')
            ->where('water_applications.id', $request->id)
            ->where('water_applications.status', 1)
            ->where('water_applicants.status', 1);
    }
}
