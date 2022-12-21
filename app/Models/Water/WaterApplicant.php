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
    public function saveWaterApplicant($applicationId,$owners)
    {
        $applicant = new WaterApplicant();
        $applicant->application_id = $applicationId;
        $applicant->applicant_name = $owners['ownerName'];
        $applicant->guardian_name = $owners['guardianName'];
        $applicant->mobile_no = $owners['mobileNo'];
        $applicant->email = $owners['email'];
        $applicant->save();
    }
}
