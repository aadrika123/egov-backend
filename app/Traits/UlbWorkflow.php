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

    // Fetch ulb Workflow in array
    public function fetchUlbWorkflow($ulb_workflow, $arr)
    {
        foreach ($ulb_workflow as $ulb_workflows) {
            $val['id'] = $ulb_workflows->id ?? '';
            $val['ulb_id'] = $ulb_workflows->ulb_id ?? '';
            $val['ulb_name'] = $ulb_workflows->ulb_name ?? '';
            $val['workflow_id'] = $ulb_workflows->workflow_id ?? '';
            $val['workflow_name'] = $ulb_workflows->workflow_name ?? '';
            $val['initiator'] = $ulb_workflows->initiator ?? '';
            $val['finisher'] = $ulb_workflows->finisher ?? '';
            $val['one_step_movement'] = $ulb_workflows->one_step_movement ?? '';
            $val['remarks'] = $ulb_workflows->remarks ?? '';
            $val['deleted_at'] = $ulb_workflows->deleted_at ?? '';
            $val['created_at'] = $ulb_workflows->created_at ?? '';
            $val['updated_at'] = $ulb_workflows->updated_at ?? '';
            array_push($arr, $val);
        }
        return response($arr);
    }
}
