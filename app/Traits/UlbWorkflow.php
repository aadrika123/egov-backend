<?php

namespace App\Traits;

/**
 * Trait for saving,editing,fetching and deleting UlbWorkflow
 */
trait UlbWorkflow
{
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
