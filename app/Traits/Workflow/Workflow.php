<?php

namespace App\Traits\Workflow;

use App\Models\WfRoleusermap;
use App\Models\WfWardUser;
use App\Models\WorkflowCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Trait for Workflows
 * Created By-Anshu Kumar
 * Created On-06-07-2022 
 * --------------------------------------------------------------------------------------
 */

trait Workflow
{
    /**
     * Function for storing or saving the workflows 
     */

    public function savingWorkflow($workflow, $request)
    {
        $workflow->module_id = $request->ModuleID;
        $workflow->workflow_name = $request->workflow_name;
        $workflow->save();
        return response()->json(['Successfully Saved The Workflow'], 200);
    }

    /**
     * Check workflow Candidate already existing
     */
    public function checkExisting($request)
    {
        return  WorkflowCandidate::where('ulb_workflow_id', '=', $request->UlbWorkflowID)
            ->where('user_id', '=', $request->UserID)
            ->first();
    }

    /**
     * Function for Saving and Editing Workflow Candidates
     */
    public function savingWorkflowCandidates($wc, $request)
    {
        $wc->ulb_workflow_id = $request->UlbWorkflowID;
        $wc->forward_id = $request->ForwardID;
        $wc->backward_id = $request->BackwardID;
        $wc->full_movement = $request->FullMovement;
        $wc->is_admin = $request->IsAdmin;
        $wc->user_id = $request->UserID;
        $wc->save();
        return response()->json('Successfully Saved the Workflow Candidates', 200);
    }

    // Fetching workflows as array
    public function fetchWorkflow($workflow, $arr)
    {
        foreach ($workflow as $workflows) {
            $val['id'] = $workflows->id ?? '';
            $val['module_id'] = $workflows->module_id ?? '';
            $val['workflow_name'] = $workflows->workflow_name ?? '';
            $val['module_name'] = $workflows->module_name ?? '';
            $val['deleted_at'] = $workflows->deleted_at ?? '';
            $val['created_at'] = $workflows->created_at ?? '';
            $val['updated_at'] = $workflows->updated_at ?? '';
            array_push($arr, $val);
        }
        return response()->json($arr, 200);
    }

    // Fetching Workflow Candidates
    public function fetchWorkflowCandidates($wc, $arr)
    {
        foreach ($wc as $wcs) {
            $val['id'] = $wcs->id ?? '';
            $val['ulb_workflow_id'] = $wcs->ulb_workflow_id ?? '';
            $val['workflow_name'] = $wcs->workflow_name ?? '';
            $val['user_id'] = $wcs->user_id ?? '';
            $val['user_name'] = $wcs->user_name ?? '';
            $val['forward_id'] = $wcs->forward_id ?? '';
            $val['forward_user'] = $wcs->forward_user ?? '';
            $val['backward_id'] = $wcs->backward_id ?? '';
            $val['backward_user'] = $wcs->backward_user ?? '';
            $val['full_movement'] = $wcs->full_movement ?? '';
            $val['is_admin'] = $wcs->is_admin ?? '';
            array_push($arr, $val);
        }
        $message = ["status" => true, "message" => "Date Fetched", "data" => $arr];
        return response()->json($message, 200);
    }

    /**
     * | Created On - 11/10/2022 
     * | Created By - Anshu Kumar
       | ----------- Function used to determine the current user while applying to any module -------- |
     * | @param workflowId > workflow id applied module
     */
    public function getWorkflowCurrentUser($workflowId)
    {
        $query = "SELECT rm.id,
                        rm.workflow_id,
                        rm.wf_role_id,
                        wr.role_name,
                        wr.forward_role_id,
                        wr.backward_role_id,
                        wr.is_initiator,
                        wr.is_finisher
                        FROM wf_workflowrolemaps rm
                        LEFT JOIN wf_roles wr ON wr.id=rm.wf_role_id
                    WHERE rm.workflow_id=$workflowId AND rm.status=1";
        $data = DB::select($query);
        return $data;
    }

    /** | Code to be used to determine initiator
    $workflows = $this->getWorkflowCurrentUser($workflow_id);
    $collectWorkflows = collect($workflows);
    $filtered = $collectWorkflows->filter(function ($value, $key) {
        return $value;
    });

    return $filtered->firstWhere('is_initiator', true);
     */

    /**
     * | get Workflow Data for Initiator
     * | @param userId > Logged In user ID
     * | @param workflowId > Workflow ID
     */
    public function getWorkflowInitiatorData($userId, $workflowId)
    {
        $query = "SELECT 
                    wf.id,
                    wf.workflow_id,
                    wf.wf_role_id,
                    r.role_name,
                    r.is_initiator,
                    r.is_finisher,
                    rum.user_id,
                    wu.ward_id
            FROM wf_workflowrolemaps  wf
            INNER JOIN (SELECT * FROM wf_roleusermaps WHERE user_id=$userId) rum ON rum.wf_role_id=wf.wf_role_id
            INNER JOIN (SELECT * FROM wf_roles WHERE is_initiator=TRUE) r ON r.id=rum.wf_role_id
            INNER JOIN (SELECT * FROM wf_ward_users WHERE user_id=$userId) wu ON wu.user_id=rum.user_id
            WHERE wf.workflow_id=$workflowId->id";
        return $query;
    }

    /**
     * | get workflow role Id by logged in User Id
     * -------------------------------------------
     * @param userId > current Logged in User
     */
    public function getRoleIdByUserId($userId)
    {
        $roles = WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->get();
        return $roles;
    }

    /**
     * | get Ward By Logged in User Id
     * -------------------------------------------
     * | @param userId > Current Logged In User Id
     */
    public function getWardByUserId($userId)
    {
        $occupiedWard = WfWardUser::select('id', 'ward_id')
            ->where('user_id', $userId)
            ->get();
        return $occupiedWard;
    }

    /**
     * | Get Initiator Id
     * | @param mixed $wfWorkflowId > Workflow Id of Modules
     * | @var string $query
     */
    public function getInitiatorId(string $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name 
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE r.is_initiator=TRUE 
                    ";
        return $query;
    }
}
