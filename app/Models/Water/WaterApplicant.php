<?php

namespace App\Models\Water;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApplicant extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';


    /**
     * |--------------------------- save new water applicants details ------------------------------|
     * | @param
     * |
        | Apply City and District
     */
    public function saveWaterApplicant($applicationId, $owners, $tenant)
    {
        $applicant = new WaterApplicant();
        $applicant->application_id  = $applicationId;
        $applicant->applicant_name  = $owners['ownerName'];
        $applicant->guardian_name   = $owners['guardianName'] ?? null;
        $applicant->mobile_no       = $owners['mobileNo'];
        $applicant->email           = $owners['email'] ?? null;
        $applicant->tenant          = $tenant ?? null;
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
            'id',
            'applicant_name',
            'guardian_name',
            'mobile_no',
            'email'
        )
            ->where('application_id', $applicationId)
            ->where('status', true);
    }

    /**
     * |-------------- Delete the applicant -------------|
     */
    public function deleteWaterApplicant($id)
    {
        WaterApplicant::where('application_id', $id)
            ->delete();
    }

    /**
     * |---------- Edit the water owner Details ----------|
     */
    public function editWaterOwners($req, $refWaterApplications)
    {
        $owner = WaterApplicant::find($req->ownerId);
        $reqs = [
            'applicant_name'  => $req->applicant_name ?? $refWaterApplications->applicant_name,
            'guardian_name'   => $req->guardian_name  ?? $refWaterApplications->guardian_name,
            'mobile_no'       => $req->mobile_no      ?? $refWaterApplications->mobile_no,
            'email'           => $req->email          ?? $refWaterApplications->email,
        ];
        $owner->update($reqs);
    }

    /**
     * | final approval and the replication of the application details 
     */
    public function finalApplicantApproval($request, $consumerId)
    {
        $approvedWaterApplicant = WaterApplicant::query()
            ->where('application_id', $request->applicationId)
            ->get();
        $checkOwner = WaterConsumerOwner::where('consumer_id', $consumerId)->first();
        if ($checkOwner) {
            throw new Exception("Water Owner Already Exist!");
        }

        # data storing in approved applicant table 
        collect($approvedWaterApplicant)->map(function ($value) use ($consumerId) {
            $approvedWaterOwners = $value->replicate();
            $approvedWaterOwners->setTable('water_approval_applicants');
            $approvedWaterOwners->id = $value->id;
            $approvedWaterOwners->save();

            $approvedWaterOwners = $value->replicate();
            $approvedWaterOwners->setTable('water_consumer_owners');
            $approvedWaterOwners->consumer_id = $consumerId;
            $approvedWaterOwners->save();
        });

        # final delete 
        WaterApplicant::where('application_id', $request->applicationId)
            ->delete();
    }


    /**
     * | Rejection Application 
     * | transfer the rejected application to the rejected table
     */
    public function finalOwnerRejection($request)
    {
        $approvedWaterApplicant = WaterApplicant::query()
            ->where('application_id', $request->applicationId)
            ->get();

        collect($approvedWaterApplicant)->map(function ($value) {
            $approvedWaterOwners = $value->replicate();
            $approvedWaterOwners->setTable('water_rejection_applicants');
            $approvedWaterOwners->id = $value->id;
            $approvedWaterOwners->save();
        });
        WaterApplicant::where('application_id', $request->applicationId)
            ->delete();
    }

    /**
     * | Deactive the Applicant In the Process of Application Edit
     * | @param applicationId
     */
    public function deactivateApplicant($applicationId)
    {
        WaterApplicant::where('application_id', $applicationId)
            ->update([
                'status' => false
            ]);
    }
}
