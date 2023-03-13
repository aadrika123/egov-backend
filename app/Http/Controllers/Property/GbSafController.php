<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\IdGeneration;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Traits\Property\Concession;
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
    use Concession;

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

            $safInbox = $this->Repository->getGbSaf($workflowIds)                                          // Repository function to get SAF Details
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

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wardId = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safData = $this->Repository->getGbSaf($workflowIds)   // Repository function to get SAF
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
                // $samHoldingDtls = $this->checkPostCondition($senderRoleId, $wfLevels, $saf);          // Check Post Next level condition
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
