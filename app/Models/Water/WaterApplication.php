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
    public function saveWaterApplication($req, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $ulbId, $applicationNo, $waterFeeId)
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
        $saveNewApplication->elec_k_no              = $req->KNo;
        $saveNewApplication->workflow_id            = $ulbWorkflowId->id;
        $saveNewApplication->connection_fee_id      = $waterFeeId;
        $saveNewApplication->initiator              = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->finisher               = collect($finisherRoleId)->first()->role_id;
        $saveNewApplication->application_no         = $applicationNo;
        $saveNewApplication->ulb_id                 = $ulbId;
        $saveNewApplication->apply_date             = date('Y-m-d H:i:s');
        $saveNewApplication->user_id                = auth()->user()->id;    // <--------- here
        $saveNewApplication->user_type              = auth()->user()->user_type;

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
                $saveNewApplication->current_role = Config::get('waterConstaint.ROLE-LABEL.DA');
                break;
            default:
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
    public function finalApproval($request, $consumerNo)
    {
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

        $mWaterConsumer = new WaterConsumer();
        $mWaterConsumer->saveWaterConsumer($approvedWaterRep, $consumerNo);
        // $approvedWater->delete();
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
        // $rejectedWater->delete();

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
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
        )
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->where('water_applications.status', true)
            ->whereBetween('water_applications.apply_date', [$req['refStartTime'], $req['refEndTime']])
            ->get();
    }
}
