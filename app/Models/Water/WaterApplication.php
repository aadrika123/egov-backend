<?php

namespace App\Models\Water;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApplication extends Model
{
    use HasFactory;

    /**
     * |------------------------------------------ Save new water applications -----------------------------------------|
     * | @param
     * | 
        |
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
        $saveNewApplication->workflow_id            = $ulbWorkflowId->id;
        $saveNewApplication->connection_fee_id      = $waterFeeId;
        $saveNewApplication->current_role           = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->initiator              = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->finisher               = collect($finisherRoleId)->first()->role_id;
        $saveNewApplication->application_no         = $applicationNo;
        $saveNewApplication->ulb_id                 = $ulbId;
        $saveNewApplication->apply_date             = date('Y-m-d H:i:s');
        $saveNewApplication->user_id                = auth()->user()->id;    // <--------- here
        $saveNewApplication->apply_from             = auth()->user()->user_type;


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

        # applied

        $saveNewApplication->save();

        return $saveNewApplication->id;
    }


    /**
     * |----------------------- Get Water Application detals With all Relation ------------------|
     * | @param 
     */
    public function fullWaterDetails($request)
    {
        return  WaterApplication::select(
            'water_applications.*',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'wf_roles.role_name AS current_role_name',
            'water_owner_type_mstrs.owner_type AS owner_char_type',
            'water_param_pipeline_types.pipeline_type'
        )
            ->join('wf_roles', 'wf_roles.id', '=', 'water_applications.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_applications.connection_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->join('water_owner_type_mstrs','water_owner_type_mstrs.id','=','water_applications.owner_type')
            ->leftjoin('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_applications.pipeline_type_id')
            ->where('water_applications.id', $request->applicationId)
            ->where('water_applications.status', 1);
    }


    /**
     * |----------------- is site is verified -------------------------|
     * | @param $req
     */
    public function markSiteVerification($id)
    {
        $activeSaf = WaterApplication::find($id);
        $activeSaf->is_field_verified = true;
        $activeSaf->save();
    }

    /**
     * |------------------ Get Application details By Id ---------------|
     */
    public function getWaterApplicationsDetails($req)
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
            ->where('water_applications.id', $req)
            ->first();
    }

    /**
     * |------------------- Delete the Application Prmanentaly ----------------------|
     */
    public function deleteWaterApplication($req)
    {
        WaterApplication::where('id', $req)
            ->delete();
    }

    /**
     * |----------------------- Edit Water Application ----------------------------|
     */
    public function editWaterApplication($req, $refWaterApplications)
    {
        $water = WaterApplication::find($req->id);

        $reqs = [
            'connection_type_id'  => $req->connection_type_id  ?? $refWaterApplications->connection_type_id,
            'property_type_id'    => $req->property_type_id    ?? $refWaterApplications->property_type_id,
            'owner_type'          => $req->owner_type          ?? $refWaterApplications->owner_type,
            'category'            => $req->category            ?? $refWaterApplications->category,
            'pipeline_type_id'    => $req->pipeline_type_id    ?? $refWaterApplications->pipeline_type_id,
            'ward_id'             => $req->ward_id             ?? $refWaterApplications->ward_id,
            'area_sqft'           => $req->area_sqft           ?? $refWaterApplications->area_sqft,
            'address'             => $req->address             ?? $refWaterApplications->address,
            'landmark'            => $req->landmark            ?? $refWaterApplications->landmark,
            'pin'                 => $req->pin                 ?? $refWaterApplications->pin,
            'flat_count'          => $req->flat_count          ?? $refWaterApplications->flat_count,
            'elec_k_no'           => $req->elec_k_no           ?? $refWaterApplications->elec_k_no,
            'elec_bind_book_no'   => $req->elec_bind_book_no   ?? $refWaterApplications->elec_bind_book_no,
            'elec_account_no'     => $req->elec_account_no     ?? $refWaterApplications->elec_account_no,
            'elec_category'       => $req->elec_category       ?? $refWaterApplications->elec_category,
            'connection_through'  => $req->connection_through  ?? $refWaterApplications->connection_through,
            'workflow_id'         => $req->workflow_id         ?? $refWaterApplications->workflow_id,
            'ulb_id'              => $req->ulb_id              ?? $refWaterApplications->ulb_id,
            'apply_date'          => $req->apply_date          ?? $refWaterApplications->apply_date,
            'user_id'             => $req->user_id             ?? $refWaterApplications->user_id,

        ];
        return $water->update($reqs);
    }
}
