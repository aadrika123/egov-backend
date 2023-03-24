<?php

namespace App\Models\Water;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WaterApplication extends Model
{
    use HasFactory;

    /**
     * |------------------------------------------ Save new water applications -----------------------------------------|
     * | @param req
     * | @return 
     * | 
     */
    public function saveWaterApplication($req, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $ulbId, $applicationNo, $waterFeeId, $newConnectionCharges)
    {

        $saveNewApplication = new WaterApplication();
        $saveNewApplication->connection_type_id     = $req->connectionTypeId;
        $saveNewApplication->property_type_id       = $req->propertyTypeId;
        $saveNewApplication->owner_type             = $req->ownerType;
        $saveNewApplication->category               = $req->category;
        $saveNewApplication->pipeline_type_id       = $req->pipelineTypeId;
        $saveNewApplication->ward_id                = $req->wardId;
        $saveNewApplication->area_sqft              = $req->areaSqft;
        $saveNewApplication->address                = $req->address;
        $saveNewApplication->landmark               = $req->landmark ?? null;
        $saveNewApplication->pin                    = $req->pin;
        $saveNewApplication->connection_through     = $req->connection_through;
        $saveNewApplication->workflow_id            = $ulbWorkflowId->id;
        $saveNewApplication->connection_fee_id      = $waterFeeId;
        $saveNewApplication->initiator              = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->finisher               = collect($finisherRoleId)->first()->role_id;
        $saveNewApplication->application_no         = $applicationNo;
        $saveNewApplication->ulb_id                 = $ulbId;
        $saveNewApplication->apply_date             = date('Y-m-d H:i:s');
        $saveNewApplication->user_id                = auth()->user()->id;    // <--------- here
        $saveNewApplication->user_type              = auth()->user()->user_type;
        $saveNewApplication->area_sqmt              = sqFtToSqMt($req->areaSqft);

        # condition entry 
        if (!is_null($req->holdingNo)) {
            $propertyId = new PropProperty();
            $propertyId = $propertyId->getPropertyId($req->holdingNo);
            $saveNewApplication->prop_id = $propertyId->id;
            $saveNewApplication->holding_no = $req->holdingNo;
        }
        if (!is_null($req->saf_no)) {
            $safId = new PropActiveSaf();
            $safId = $safId->getSafId($req->saf_no);
            $saveNewApplication->saf_id = $safId->id;
            $saveNewApplication->saf_no = $req->saf_no;
        }

        switch ($saveNewApplication->user_type) {
            case ('Citizen'):
                $saveNewApplication->apply_from = "Online";
                if ($newConnectionCharges['conn_fee_charge']['amount'] == 0) {
                    $saveNewApplication->payment_status = 1;
                }
                break;
            case ('JSK'):
                if ($newConnectionCharges['conn_fee_charge']['amount'] == 0) {
                    $saveNewApplication->payment_status = 1;
                }
                break;
            default: # Check
                $saveNewApplication->apply_from = auth()->user()->user_type;
                $saveNewApplication->current_role = Config::get('waterConstaint.ROLE-LABEL.BO');
                break;
        }

        $saveNewApplication->save();

        return $saveNewApplication->id;
    }


    /**
     * |----------------------- Get Water Application detals With all Relation ------------------|
     * | @param request
     * | @return 
     */
    public function fullWaterDetails($request)
    {
        return  WaterApplication::select(
            'water_applications.*',
            'water_applications.connection_through as connection_through_id',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'wf_roles.role_name AS current_role_name',
            'water_owner_type_mstrs.owner_type AS owner_char_type',
            'water_param_pipeline_types.pipeline_type'
        )
            ->leftjoin('wf_roles', 'wf_roles.id', '=', 'water_applications.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_applications.connection_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_applications.owner_type')
            ->leftjoin('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_applications.pipeline_type_id')
            ->where('water_applications.id', $request->applicationId)
            ->where('water_applications.status', 1);
    }


    /**
     * |----------------- is site is verified -------------------------|
     * | @param id
     */
    public function markSiteVerification($id)
    {
        $activeSaf = WaterApplication::find($id);
        $activeSaf->is_field_verified = true;
        $activeSaf->save();
    }

    /**
     * |------------------ Get Application details By Id ---------------|
     * | @param applicationId
     */
    public function getWaterApplicationsDetails($applicationId)
    {
        return WaterApplication::select(
            'water_applications.*',
            'water_applicants.id as ownerId',
            'water_applicants.applicant_name',
            'water_applicants.guardian_name',
            'water_applicants.city',
            'water_applicants.mobile_no',
            'water_applicants.email',
            'water_applicants.status',
            'water_applicants.district'

        )
            ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
            ->where('water_applications.id', $applicationId)
            ->firstOrFail();
    }

    /**
     * |------------------- Delete the Application Prmanentaly ----------------------|
     * | @param req
     */
    public function deleteWaterApplication($req)
    {
        WaterApplication::where('id', $req)
            ->delete();
    }

    /**
     * |------------------- Get Water Application By Id -------------------|
     * | @param applicationId
     */
    public function getApplicationById($applicationId)
    {
        return  WaterApplication::where('id', $applicationId)
            ->where('status', true);
    }


    /**
     * |------------------- Get the Application details by applicationNo -------------------|
     * | @param applicationNo
     * | @param connectionTypes 
     * | @return 
     */
    public function getDetailsByApplicationNo($connectionTypes, $applicationNo)
    {
        return WaterApplication::select(
            'water_applications.id',
            'water_applications.application_no',
            'water_applications.ward_id',
            'water_applications.address',
            'water_applications.holding_no',
            'water_applications.saf_no',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_applicants.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_applicants.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_applicants.guardian_name,',') as guardianName"),
        )
            ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_applications.ward_id')
            ->where('water_applications.status', true)
            ->where('water_applications.connection_type_id', $connectionTypes)
            ->where('water_applications.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_applications.ulb_id', auth()->user()->ulb_id)
            ->groupBy('water_applications.saf_no', 'water_applications.holding_no', 'water_applications.address', 'water_applications.id', 'water_applicants.application_id', 'water_applications.application_no', 'water_applications.ward_id', 'ulb_ward_masters.ward_name')
            ->get();
    }

    /**
     * |------------------- Final Approval of the water application -------------------|
     * | @param request
     * | @param consumerNo
     */
    public function finalApproval($request, $consumerNo, $refJe)
    {
        $mWaterSiteInspection = new WaterSiteInspection();
        $approvedWater = WaterApplication::query()
            ->where('id', $request->applicationId)
            ->first();

        $checkExist = WaterApprovalApplicationDetail::where('id', $approvedWater->id)->first();
        if ($checkExist) {
            throw new Exception("Access Denied ! Consumer Already Exist!");
        }
        $checkconsumer = WaterConsumer::where('id', $approvedWater->id)->first();
        if ($checkconsumer) {
            throw new Exception("Access Denied ! Consumer Already Exist!");
        }

        $approvedWaterRep = $approvedWater->replicate();
        $approvedWaterRep->setTable('water_approval_application_details');
        $approvedWaterRep->id = $approvedWater->id;
        $approvedWaterRep->save();

        $siteDetails = $mWaterSiteInspection->getSiteDetails($request->applicationId)
            ->where('payment_status', 1)
            ->where('order_officer', $refJe)
            ->first();
        if (isset($siteDetails)) {
            $approvedWaterRep = [
                'connection_type_id'    => $siteDetails['connection_type_id'],
                'connection_through'    => $siteDetails['connection_through'],
                'pipeline_type_id'      => $siteDetails['pipeline_type_id'],
                'property_type_id'      => $siteDetails['property_type_id'],
                'category'              => $siteDetails['category'],
                'area_sqft'             => $siteDetails['area_sqft'],
                'area_asmt'             => sqFtToSqMt($siteDetails['area_sqft'])
            ];
        }

        $mWaterConsumer = new WaterConsumer();
        $consumerId = $mWaterConsumer->saveWaterConsumer($approvedWaterRep, $consumerNo);
        $approvedWater->delete();
        return $consumerId;
    }

    /**
     * |------------------- Final rejection of the Application -------------------|
     * | Transfer the data to new table
     */
    public function finalRejectionOfAppication($request)
    {
        $rejectedWater = WaterApplication::query()
            ->where('id', $request->applicationId)
            ->first();

        $rejectedWaterRep = $rejectedWater->replicate();
        $rejectedWaterRep->setTable('water_rejection_application_details');
        $rejectedWaterRep->id = $rejectedWater->id;
        $rejectedWaterRep->save();
        $rejectedWater->delete();
    }

    /**
     * |------------------- Edit the details of the application -------------------|
     * | Send the details of the apllication in the audit table
     */
    public function editWaterApplication($applicationId)
    {
    }

    /**
     * |------------------- Deactivate the Water Application In the Process of Aplication Editing -------------------|
     * | @param ApplicationId
     */
    public function deactivateApplication($applicationId)
    {
        WaterApplication::where('id', $applicationId)
            ->update([
                'status' => false
            ]);
    }

    /**
     * |------------------- Get Water Application Details According to the UserType and Date -------------------|
     * | @param request
     */
    public function getapplicationByDate($req)
    {
        return WaterApplication::select(
            'water_applications.id',
            'water_applications.*',
            'water_applications.connection_through as connection_through_id',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
        )

            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->where('water_applications.status', true)
            ->whereBetween('water_applications.apply_date', [$req['refStartTime'], $req['refEndTime']]);
    }


    /**
     * | Search Application Using the application NO
     * | @param applicationNo
     */
    public function getApplicationByNo($applicationNo, $roleId)
    {
        return  WaterApplication::select(
            'water_applications.*',
            'water_applications.connection_through as connection_through_id',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'water_owner_type_mstrs.owner_type AS owner_char_type',
            'water_param_pipeline_types.pipeline_type'
        )
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_applications.connection_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_applications.owner_type')
            ->leftjoin('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_applications.pipeline_type_id')
            ->where('water_applications.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_applications.current_role', $roleId)
            ->where('water_applications.status', 1);
    }


    /**
     * | Update payment Status for the application
     * | Only used in the process of site inspection
     * | @param applicationNo
     * | @param action
     */
    public function updatePaymentStatus($applicationId, $action)
    {
        switch ($action) {
            case (false):
                WaterApplication::where('id', $applicationId)
                    ->update([
                        'payment_status' => 0,
                        'is_field_verified' => true
                    ]);
                break;

            case (true):
                WaterApplication::where('id', $applicationId)
                    ->update([
                        'payment_status' => 1,
                        'is_field_verified' => true
                    ]);
                break;
        }
    }


    /**
     * |------------------- Get the Application details by applicationNo -------------------|
     * | @param applicationNo
     * | @param connectionTypes 
     * | @return 
     */
    public function getDetailsByApplicationId($applicationId)
    {
        return WaterApplication::select(
            'water_applications.id',
            'water_applications.application_no',
            'water_applications.ward_id',
            'water_applications.address',
            'water_applications.holding_no',
            'water_applications.saf_no',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            DB::raw("string_agg(water_applicants.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_applicants.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_applicants.guardian_name,',') as guardianName"),
        )
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_applications.ward_id')
            ->where('water_applications.status', true)
            ->where('water_applications.id', $applicationId)
            ->groupBy(
                'water_applications.saf_no',
                'water_applications.holding_no',
                'water_applications.address',
                'water_applications.id',
                'water_applicants.application_id',
                'water_applications.application_no',
                'water_applications.ward_id',
                'water_applications.ulb_id',
                'ulb_ward_masters.ward_name',
                'ulb_masters.id',
                'ulb_masters.ulb_name'
            );
    }


    /**
     * | Deactivate the Doc Upload Status
     * | @param applicationId
     */
    public function deactivateUploadStatus($applicationId)
    {
        WaterApplication::where('id', $applicationId)
            ->update([
                'doc_upload_status' => false
            ]);
    }

    /**
     * | Activate the Doc Upload Status
     */
    public function activateUploadStatus($applicationId)
    {
        WaterApplication::where('id', $applicationId)
            ->update([
                'doc_upload_status' => true
            ]);
    }

    /**
     * | update the current role in case of online citizen apply
     */
    public function updateCurrentRoleForDa($applicationId, $waterRoles)
    {
        WaterApplication::where('id', $applicationId)
            ->update([
                'current_role' => $waterRoles['DA']
            ]);
    }

    /**
     * | Save The payment Status 
     * | @param ApplicationId
     */
    public function updateOnlyPaymentstatus($applicationId)
    {
        $activeSaf = WaterApplication::find($applicationId);
        $activeSaf->payment_status = 1;
        $activeSaf->save();
    }


    /**
     * | Update the payment Status ini case of pending
     * | in case of application is under verification 
     * | @param applicationId
     */
    public function updatePendingStatus($applicationId)
    {
        $activeSaf = WaterApplication::find($applicationId);
        $activeSaf->payment_status = 2;
        $activeSaf->save();
    }


    #--------------------------------------------------------------------------------------------------------------------#

    /**
     * | Dash bording 
     */
    public function getJskAppliedApplications()
    {
        $refUserType = authUser()->user_type;
        $currentDate = Carbon::now()->format('Y-m-d');

        return WaterApplication::select(
            'water_applications.*',
            DB::raw("string_agg(water_applicants.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_applicants.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_applicants.guardian_name,',') as guardianName"),
        )
            ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
            ->where('apply_date', $currentDate)
            ->where('user_type', $refUserType)
            ->where('water_applications.status', true)
            ->where('water_applicants.status', true)
            ->groupBy(
                'water_applications.id',
                'water_applicants.application_id',
            )
            ->get();
    }

    /**
     * | Get application According to current role
     */
    public function getApplicationByRole($roleId)
    {
        return WaterApplication::where('current_role', $roleId)
            ->where('status', true);
    }
}
