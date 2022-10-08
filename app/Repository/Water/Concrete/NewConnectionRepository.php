<?php

namespace App\Repository\Water\Concrete;

use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication;
use App\Repository\Water\Interfaces\iNewConnection;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 */

class NewConnectionRepository implements iNewConnection
{
    /**
     * | -------------  Apply for the new Application for Water Application ------------- |
     */
    public function store(Request $req)
    {
        DB::beginTransaction();
        try {
            $newApplication = new WaterApplication();
            $newApplication->connection_type_id = $req->connectionTypeId;
            $newApplication->property_type_id = $req->propertyTypeId;
            $newApplication->owner_type = $req->ownerType;
            $newApplication->proof_document_id = $req->proofDocumentId;
            $newApplication->category = $req->category;
            $newApplication->pipeline_type_id = $req->pipelineTypeId;

            $newApplication->holding_no = $req->holdingNo;
            $newApplication->ward_id = $req->wardId;
            $newApplication->area_sqft = $req->areaSqft;
            $newApplication->address = $req->address;
            $newApplication->landmark = $req->landmark;
            $newApplication->pin = $req->pin;
            $newApplication->flat_count = $req->flatCount;

            $newApplication->elec_k_no = $req->elecKNo;
            $newApplication->elec_bind_book_no = $req->elecBindBookNo;
            $newApplication->elec_account_no = $req->elecAccountNo;
            $newApplication->elec_category = $req->elecCategory;

            // Generating Application No 
            $now = new DateTime();
            $applicationNo = 'APP' . $now->getTimeStamp();
            $newApplication->application_no = $applicationNo;
            $newApplication->save();

            // Water Applicants Owners
            $owner = $req['owners'];
            foreach ($owner as $owners) {
                $applicant = new WaterApplicant();
                $applicant->application_id = $newApplication->id;
                $applicant->applicant_name = $owners['ownerName'];
                $applicant->guardian_name = $owners['guardianName'];
                $applicant->mobile_no = $owners['guardianName'];
                $applicant->email = $owners['guardianName'];
                $applicant->save();
            }

            DB::commit();
            return responseMsg(true, "Successfully Saved", $applicationNo);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }
}
