<?php

namespace App\Traits\Workflow;

use App\Models\WorkflowCandidate;


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
        return response()->json($arr, 200);
    }
}
