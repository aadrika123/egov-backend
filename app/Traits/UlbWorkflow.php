<?php

namespace App\Traits;

use App\Models\UlbWorkflowMaster;

/**
 * Trait for saving,editing,fetching and deleting UlbWorkflow
 */
trait UlbWorkflow
{
    /**
     * Checking if the ulb id is already existing for the workflow_id or not
     */
    public function checkExisting($request)
    {
        return UlbWorkflowMaster::where('ulb_id', $request->UlbID)
            ->where('workflow_id', $request->workflow_id)
            ->first();
    }
    /**
     * Saving and editing the ulbworkflow
     */
    public function saving($ulb_workflow, $request)
    {
        $ulb_workflow->ulb_id = $request->UlbID;
        $ulb_workflow->workflow_id = $request->workflow_id;
        $ulb_workflow->initiator = $request->Initiator;
        $ulb_workflow->finisher = $request->Finisher;
        $ulb_workflow->one_step_movement = $request->OneStepMovement;
        $ulb_workflow->remarks = $request->Remarks;
        $ulb_workflow->save();
    }
}
