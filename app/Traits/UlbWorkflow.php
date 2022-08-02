<?php

namespace App\Traits;

use App\Models\UlbWorkflowMaster;
use App\Models\WorkflowCandidate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

/**
 * Trait for saving,editing,fetching and deleting UlbWorkflow
 */
trait UlbWorkflow
{
    /**
     * Checking if the ModuleID is already existing for the ULBID or not
     */
    public function checkUlbModuleExistance($request)
    {
        return UlbWorkflowMaster::where('ulb_id', $request->UlbID)
            ->where('module_id', $request->ModuleID)
            ->first();
    }

    /**
     * Saving and editing the ulbworkflow
     * @param UlbWorkflowMasters Model $ulb_workflow
     * @param Request $request
     * #ref_cand = contains the value of request Candidate IDs in a array
     * Save Candidates ID in Workflow Candidates using loop
     * @return Response
     */
    public function saving($ulb_workflow, $request)
    {
        $ulb_workflow->workflow_id = $request->workflow_id;
        $ulb_workflow->module_id = $request->ModuleID;
        $ulb_workflow->ulb_id = $request->UlbID;
        $ulb_workflow->initiator = $request->Initiator;
        $ulb_workflow->finisher = $request->Finisher;
        $ulb_workflow->one_step_movement = $request->OneStepMovement;
        $ulb_workflow->remarks = $request->Remarks;
        $ulb_workflow->save();

        // Save Candidate ID with array with looping
        $ref_cand = $request['Candidates'];
        foreach ($ref_cand as $ref_cands) {
            $wc = new WorkflowCandidate;
            $wc->ulb_workflow_id = $ulb_workflow->id;
            $wc->user_id = $ref_cands;
            $wc->save();
        }
        return response()->json([
            'WorkflowID' => $request->workflow_id,
            'ModuleID' => $request->ModuleID,
            'ULBID' => $request->UlbID
        ], 200);
    }

    /**
     * Delete Existing Workflow Candidates
     */
    public function deleteExistingCandidates($id)
    {
        // $candidate = WorkflowCandidate::select('id')
        //     ->where('ulb_workflow_id', $id)->get();
        // $mUrl = URL::to('/');
        // foreach ($candidate as $candidates) {
        //     $api = $mUrl . "/api/delete-workflow-candidates/" . $candidates->id;
        //     array_push($a, $api);
        //     Http::delete($api);
        // }
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
