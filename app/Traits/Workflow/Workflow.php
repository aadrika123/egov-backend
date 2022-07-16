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
}
