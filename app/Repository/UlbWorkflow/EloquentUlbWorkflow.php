<?php

namespace App\Repository\UlbWorkflow;

use App\Repository\UlbWorkflow\UlbWorkflow;
use App\Models\UlbWorkflowMaster;
use Illuminate\Http\Request;
use Exception;

/**
 * Repository for Ulb Workflows Store, fetch, edit and destroy
 * Created On-14-07-2022 
 * Created By-Anshu Kumar
 */
class EloquentUlbWorkflow implements UlbWorkflow
{
    /**
     * Storing UlbWorkflows
     */
    public function store(Request $request)
    {
        $request->validate([
            'workflow_id' => "required|unique:ulb_workflow_masters"
        ]);

        try {
            $ulb_workflow = new UlbWorkflowMaster;
            $ulb_workflow->ulb_id = $request->UlbID;
            $ulb_workflow->workflow_id = $request->workflow_id;
            $ulb_workflow->initiator = $request->Initiator;
            $ulb_workflow->finisher = $request->Finisher;
            $ulb_workflow->one_step_movement = $request->OneStepMovement;
            $ulb_workflow->remarks = $request->Remarks;
            $ulb_workflow->save();
            return response()->json('Successfully Saved the Ulb Workflow', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
