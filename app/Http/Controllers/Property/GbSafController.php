<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqGbSiteVerification;
use App\MicroServices\IdGeneration;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropFloor;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Exception;

/**
 * | Created On-13-03-2023
 * | Created by-Mrinal Kumar
 * | GB SAF Workflow
 */

class GbSafController extends Controller
{

    /**
     * | Inbox for GB Saf
     */
    public function inbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                       // Model () to get Occupied Wards of Current User

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $mpropActiveSafs->getGbSaf($workflowIds)                                          // Repository function to get SAF Details
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safInbox->values()), "010103", "1.0", "339ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Outbox for GB Saf
     */
    public function outbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wardId = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safData = $mpropActiveSafs->getGbSaf($workflowIds)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData->values()), "010104", "1.0", "274ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Fields Verified Inbox
     */
    public function fieldVerifiedInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list
            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $mpropActiveSafs->getGbSaf($workflowIds)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->get();

            return responseMsgs(true, "field Verified Inbox!", remove_null($safInbox), 010125, 1.0, "", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $mDeviceId);
        }
    }



    /**
     * | Post next level
     */
    public function postNextLevel(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
            'receiverRoleId' => 'nullable|integer',
            'action' => 'required|In:forward,backward'
        ]);

        try {
            // Variable Assigments
            $userId = authUser()->id;
            $wfLevels = Config::get('PropertyConstaint.GBSAF-LABEL');
            $saf = PropActiveSaf::findOrFail($request->applicationId);
            $mWfMstr = new WfWorkflow();
            $track = new WorkflowTrack();
            $mWfWorkflows = new WfWorkflow();
            $mWfRoleMaps = new WfWorkflowrolemap();
            $samHoldingDtls = array();

            // Derivative Assignments
            $senderRoleId = $saf->current_role;
            $request->validate([
                'comment' => $senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',

            ]);
            $ulbWorkflowId = $saf->workflow_id;
            $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);
            DB::beginTransaction();
            if ($request->action == 'forward') {
                $wfMstrId = $mWfMstr->getWfMstrByWorkflowId($saf->workflow_id);
                $samHoldingDtls = $this->checkPostCondition($senderRoleId, $wfLevels, $saf);          // Check Post Next level condition
                $saf->current_role = $forwardBackwardIds->forward_role_id;
                $saf->last_role_id =  $forwardBackwardIds->forward_role_id;                     // Update Last Role Id
                $metaReqs['verificationStatus'] = 1;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
            }
            // SAF Application Update Current Role Updation
            if ($request->action == 'backward') {
                $saf->current_role = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->backward_role_id;
            }


            $saf->save();
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = Config::get('PropertyConstaint.SAF_REF_TABLE');
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;

            $request->request->add($metaReqs);

            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", $samHoldingDtls, "010109", "1.0", "", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", "010109", "1.0", "", "POST", $request->deviceId);
        }
    }

    /**
     * | check Post Condition for backward forward(9.1)
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $saf)
    {
        // Variable Assigments
        $reAssessWfMstrId = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
        $mPropSafDemand = new PropSafsDemand();
        $mPropMemoDtl = new PropSafMemoDtl();
        $todayDate = Carbon::now()->format('Y-m-d');
        $fYear = calculateFYear($todayDate);
        $idGeneration = new IdGeneration;
        $ptNo = $idGeneration->generatePtNo(true, $saf->ulb_id);

        // Derivative Assignments
        $demand = $mPropSafDemand->getFirstDemandByFyearSafId($saf->id, $fYear);
        if (collect($demand)->isEmpty())
            throw new Exception("Demand Not Available for the Current Year to Generate SAM");
        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($saf->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;

            case $wfLevels['DA']:                       // DA Condition
                if ($saf->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");

                $saf->pt_no = $ptNo;                        // Generate New Property Tax No for All Conditions
                $saf->save();
                $samNo = "SAM-" . $saf->id;                 // Generate SAM No
                $mergedDemand = array_merge($demand->toArray(), [
                    'memo_type' => 'SAM',
                    'memo_no' => $samNo,
                    'pt_no' => $ptNo,
                    'ward_id' => $saf->ward_mstr_id
                ]);
                $memoReqs = new Request($mergedDemand);
                $mPropMemoDtl->postSafMemoDtls($memoReqs);
                $this->replicateSaf($saf->id);
                break;

            case $wfLevels['TC']:
                if ($saf->is_geo_tagged == false)
                    throw new Exception("Geo Tagging Not Done");
            case $wfLevels['UTC']:
                if ($saf->is_field_verified == false)
                    throw new Exception("Field Verification Not Done");
        }
        return [
            'holdingNo' =>  $holdingNo ?? "",
            'samNo' => $samNo ?? ""
        ];
    }

    /**
     * | Replicate Tables of saf to property
     */
    public function replicateSaf($safId)
    {
        $activeSaf = PropActiveSaf::query()
            ->where('id', $safId)
            ->first();
        $floorDetails = PropActiveSafsFloor::query()
            ->where('saf_id', $safId)
            ->get();

        $toBeProperties = PropActiveSaf::query()
            ->where('id', $safId)
            ->select(
                'ulb_id',
                'cluster_id',
                'holding_no',
                'applicant_name',
                'ward_mstr_id',
                'ownership_type_mstr_id',
                'prop_type_mstr_id',
                'appartment_name',
                'no_electric_connection',
                'elect_consumer_no',
                'elect_acc_no',
                'elect_bind_book_no',
                'elect_cons_category',
                'building_plan_approval_no',
                'building_plan_approval_date',
                'water_conn_no',
                'water_conn_date',
                'khata_no',
                'plot_no',
                'village_mauja_name',
                'road_type_mstr_id',
                'road_width',
                'area_of_plot',
                'prop_address',
                'prop_city',
                'prop_dist',
                'prop_pin_code',
                'prop_state',
                'corr_address',
                'corr_city',
                'corr_dist',
                'corr_pin_code',
                'corr_state',
                'is_mobile_tower',
                'tower_area',
                'tower_installation_date',
                'is_hoarding_board',
                'hoarding_area',
                'hoarding_installation_date',
                'is_petrol_pump',
                'under_ground_area',
                'petrol_pump_completion_date',
                'is_water_harvesting',
                'land_occupation_date',
                'new_ward_mstr_id',
                'zone_mstr_id',
                'flat_registry_date',
                'assessment_type',
                'holding_type',
                'apartment_details_id',
                'ip_address',
                'status',
                'user_id',
                'citizen_id',
                'pt_no',
                'building_name',
                'street_name',
                'location',
                'landmark',
                'is_gb_saf',
                'gb_office_name',
                'gb_usage_types',
                'gb_prop_usage_types'
            )->first();

        $assessmentType = $activeSaf->assessment_type;

        if (in_array($assessmentType, ['New Assessment'])) { // Make New Property For New Assessment
            $propProperties = $toBeProperties->replicate();
            $propProperties->setTable('prop_properties');
            $propProperties->saf_id = $activeSaf->id;
            $propProperties->new_holding_no = $activeSaf->new_holding_no;
            $propProperties->save();

            // SAF Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $propFloor = $floorDetail->replicate();
                $propFloor->setTable('prop_floors');
                $propFloor->property_id = $propProperties->id;
                $propFloor->save();
            }
        }

        // Edit In Case of Reassessment,Mutation
        if (in_array($assessmentType, ['Re Assessment'])) {         // Edit Property In case of Reassessment
            $propId = $activeSaf->previous_holding_id;
            $mProperty = new PropProperty();
            $mPropFloors = new PropFloor();
            // Edit Property
            $mProperty->editPropBySaf($propId, $activeSaf);

            // Edit Floors
            foreach ($floorDetails as $floorDetail) {
                $ifFloorExist = $mPropFloors->getFloorByFloorId($floorDetail->prop_floor_details_id);
                $floorReqs = new Request([
                    'floor_mstr_id' => $floorDetail->floor_mstr_id,
                    'usage_type_mstr_id' => $floorDetail->usage_type_id,
                    'const_type_mstr_id' => $floorDetail->construction_type_id,
                    'occupancy_type_mstr_id' => $floorDetail->occupancy_type_id,
                    'builtup_area' => $floorDetail->builtup_area,
                    'date_from' => $floorDetail->date_from,
                    'date_upto' => $floorDetail->date_to,
                    'carpet_area' => $floorDetail->carpet_area,
                    'property_id' => $propId,
                    'saf_id' => $safId

                ]);
                if ($ifFloorExist) {
                    $mPropFloors->editFloor($ifFloorExist, $floorReqs);
                } else
                    $mPropFloors->postFloor($floorReqs);
            }
        }
    }

    /**
     * | Site Verification
     * | @param req requested parameter
     * | Status-Closed
     */
    public function siteVerification(ReqGbSiteVerification $req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $propActiveSaf = new PropActiveSaf();
            $verification = new PropSafVerification();
            $mWfRoleUsermap = new WfRoleusermap();
            $verificationDtl = new PropSafVerificationDtl();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;

            $safDtls = $propActiveSaf->getSafNo($req->safId);
            $workflowId = $safDtls->workflow_id;
            $roadWidthType = $this->readRoadWidthType($req->roadWidth);                                 // Read Road Width Type by Trait
            $getRoleReq = new Request([                                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);

            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            DB::beginTransaction();
            switch ($roleId) {
                case $taxCollectorRole:                                                                  // In Case of Agency TAX Collector
                    $req->agencyVerification = true;
                    $req->ulbVerification = false;
                    $msg = "Site Successfully Verified";
                    break;
                case $ulbTaxCollectorRole:                                                                // In Case of Ulb Tax Collector
                    $req->agencyVerification = false;
                    $req->ulbVerification = true;
                    $msg = "Site Successfully Verified";
                    $propActiveSaf->verifyFieldStatus($req->safId);                                         // Enable Fields Verify Status
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }
            $req->merge(['roadType' => $roadWidthType, 'userId' => $userId, 'ulbId' => $ulbId]);
            // Verification Store
            $verificationId = $verification->store($req);                            // Model function to store verification and get the id
            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floor as $floorDetail) {
                if ($floorDetail['useType'] == 1)
                    $carpetArea =  $floorDetail['buildupArea'] * 0.70;
                else
                    $carpetArea =  $floorDetail['buildupArea'] * 0.80;

                $floorReq = [
                    'verification_id' => $verificationId,
                    'saf_id' => $req->safId,
                    'saf_floor_id' => $floorDetail['floorId'] ?? null,
                    'floor_mstr_id' => $floorDetail['floorNo'],
                    'usage_type_id' => $floorDetail['useType'],
                    'construction_type_id' => $floorDetail['constructionType'],
                    'occupancy_type_id' => $floorDetail['occupancyType'],
                    'builtup_area' => $floorDetail['buildupArea'],
                    'date_from' => $floorDetail['dateFrom'],
                    'date_to' => $floorDetail['dateUpto'],
                    'carpet_area' => $carpetArea,
                    'user_id' => $userId,
                    'ulb_id' => $ulbId
                ];
                $verificationDtl->store($floorReq);
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function approvalRejectionGbSaf(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        try {
            // Check if the Current User is Finisher or Not (Variable Assignments)
            $safDetails = PropActiveSaf::findOrFail($req->applicationId);
            $mWfRoleUsermap = new WfRoleusermap();
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropSafDemand = new PropSafsDemand();
            $idGeneration = new IdGeneration;
            $ptNo = $idGeneration->generatePtNo(true, $safDetails->ulb_id);
            $todayDate = Carbon::now()->format('Y-m-d');
            $currentFinYear = calculateFYear($todayDate);

            $userId = authUser()->id;
            $safId = $req->applicationId;
            // Derivative Assignments
            $workflowId = $safDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            if ($safDetails->finisher_role_id != $roleId)
                throw new Exception("Forbidden Access");
            $activeSaf = PropActiveSaf::query()
                ->where('id', $req->applicationId)
                ->first();
            $floorDetails = PropActiveSafsFloor::query()
                ->where('saf_id', $req->applicationId)
                ->get();

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $safDetails->saf_pending_status = 0;
                $safDetails->pt_no = $ptNo;
                $safDetails->save();


                $demand = $mPropSafDemand->getFirstDemandByFyearSafId($safId, $currentFinYear);
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for the Current Year to Generate FAM");
                // SAF Application replication
                $famNo = "FAM-" . $safId;
                $mergedDemand = array_merge($demand->toArray(), [
                    'memo_type' => 'FAM',
                    'memo_no' => $famNo,
                    'holding_no' => $activeSaf->new_holding_no ?? $activeSaf->holding_no,
                    'pt_no' => $activeSaf->pt_no,
                    'ward_id' => $activeSaf->ward_mstr_id,
                    'saf_id' => $safId
                ]);

                $memoReqs = new Request($mergedDemand);
                $mPropSafMemoDtl->postSafMemoDtls($memoReqs);
                $this->finalApprovalSafReplica($activeSaf, $floorDetails, $ptNo);
                $msg = "Application Approved Successfully";
            }
            // Rejection
            if ($req->status == 0) {
                $this->finalRejectionSafReplica($activeSaf, $floorDetails);
                $msg = "Application Rejected Successfully";
            }

            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->pt_no], "010110", "1.0", "410ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Replication of Final Approval SAf(10.1)
     */
    public function finalApprovalSafReplica($activeSaf, $floorDetails, $ptNo)
    {

        // Approveed SAF Application replication
        $approvedSaf = $activeSaf->replicate();
        $approvedSaf->setTable('prop_safs');
        $approvedSaf->id = $activeSaf->id;
        $approvedSaf->pt_no = $ptNo;
        $approvedSaf->push();
        $activeSaf->delete();

        // Saf Floors Replication
        foreach ($floorDetails as $floorDetail) {
            $approvedFloor = $floorDetail->replicate();
            $approvedFloor->setTable('prop_safs_floors');
            $approvedFloor->id = $floorDetail->id;
            $approvedFloor->save();
            $floorDetail->delete();
        }
    }

    /**
     * | Replication of Final Rejection Saf(10.2)
     */
    public function finalRejectionSafReplica($activeSaf, $floorDetails)
    {
        // Rejected SAF Application replication
        $rejectedSaf = $activeSaf->replicate();
        $rejectedSaf->setTable('prop_rejected_safs');
        $rejectedSaf->id = $activeSaf->id;
        $rejectedSaf->push();
        $activeSaf->delete();

        // SAF Floors Replication
        foreach ($floorDetails as $floorDetail) {
            $approvedFloor = $floorDetail->replicate();
            $approvedFloor->setTable('prop_rejected_safs_floors');
            $approvedFloor->id = $floorDetail->id;
            $approvedFloor->save();
            $floorDetail->delete();
        }
    }
}
