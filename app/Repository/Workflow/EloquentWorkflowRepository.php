<?php

namespace App\Repository\Workflow;

use App\Repository\Workflow\WorkflowRepository;
use Illuminate\Http\Request;
use App\Models\Workflow;
use App\Models\WorkflowCandidate;
use Exception;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Saving, editing Workflows
 * Created On-06-07-2022 
 * Created By-Anshu Kumar
 * --------------------------------------------------------------------------------------------
 * Code Tested By-
 * Code Testing Date-
 * --------------------------------------------------------------------------------------------
 * 
 */

class EloquentWorkflowRepository implements WorkflowRepository
{
    use WorkflowTrait;

    /**
     * Store Workflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * ----------------------------------------------------------------------------------------
     * Business Logic
     * ----------------------------------------------------------------------------------------
     * Validating workflow(workflow should be unique)
     * Save in Database
     * @return response
     * 
     */
    public function storeWorkflow(Request $request)
    {
        // Validating
        $request->validate([
            'workflow_name' => 'required|unique:workflows'
        ]);

        try {
            // Store
            $workflow = new Workflow;
            return $this->savingWorkflow($workflow, $request);           // Trait for Storing
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Workflow By Ids
     * @param mixed $id
     */
    public function viewWorkflow($id)
    {
        $workflow = DB::select("
                        select w.* ,
                        m.module_name
                        from workflows w
                        left join module_masters m on m.id=w.module_id
                        where w.id=$id
        ");
        if ($workflow) {
            return response()->json($workflow, 200);
        } else {
            return response()->json('Workflow Not Available for this id', 404);
        }
    }

    /**
     * Get all workflows
     */
    public function getAllWorkflows()
    {
        $data = DB::select("
                        select w.* ,
                        m.module_name
                        from workflows w
                        left join module_masters m on m.id=w.module_id
                        where w.deleted_at is null
                        order by w.id desc
                        ");
        return $data;
    }

    /**
     * Update Workflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @param mixed $id
     * ------------------------------------------------------------------------------------------
     * Business Logic
     * ------------------------------------------------------------------------------------------
     * 1. check validation of workflowname
     * 2. if workflow name is same as previous then update only initiator and finisher
     * 3. if workflowname is different then check if data already existing or not 
     * 4. store Using @return App\Traits\Workflow\Workflow 
     */

    public function updateWorkflow(Request $request, $id)
    {
        // Validate
        $request->validate([
            'workflow_name' => 'required'
        ]);
        $workflow = Workflow::find($id);
        $stmt = $workflow->workflow_name == $request->workflow_name;
        if ($stmt) {
            return $this->savingWorkflow($workflow, $request);           // Trait for Storing
        }
        if (!$stmt) {
            // Checking Already Existing
            $request->validate([
                'workflow_name' => 'unique:workflows'
            ]);
            return $this->savingWorkflow($workflow, $request);           // Trait for Storing
        }
    }

    /**
     * Delete Workflows
     */
    public function deleteWorkflow($id)
    {
        $workflow = Workflow::find($id);
        if ($workflow == null) {
            return response()->json('Workflow has been already deleted', 400);
        } else {
            $workflow->delete();
        }
        return response()->json('Successfully Deleted', 200);
    }

    /**
     * Store Workflow Candidates
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * -----------------------------------------------------------------------------------------
     * Business Logic
     * -----------------------------------------------------------------------------------------
     * Validate WorkflowID and EmployeeID
     * Store
     * @return response 
     */

    public function storeWorkflowCandidate(Request $request)
    {
        // Validating
        $request->validate([
            'ulb_workflow_id' => 'required|int|unique:workflow_candidates'
        ]);

        try {
            // Storing
            $wc = new WorkflowCandidate;
            return $this->savingWorkflowCandidates($wc, $request);          // Editing Using Trait
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * View Workflow Candidates details by Id
     * @param mixed $id
     * -----------------------------------------------------------------------------------------
     */

    public function viewWorkflowCandidates($id)
    {
        $wc = WorkflowCandidate::find($id);
        return $wc;
    }

    /**
     * Edit Workflow Candidates
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @param mixed $id
     * --------------------------------------------------------------------------------------------
     * Business Logics
     * ---------------------------------------------------------------------------------------------
     * Checking Validation 
     * Edit Candidates
     * @return App\Traits\Workflow savingWorkflowCandidates()
     */

    public function editWorkflowCandidates(Request $request, $id)
    {
        // Validating
        $request->validate([
            'ulb_workflow_id' => 'required|int'
        ]);

        try {
            $wc = WorkflowCandidate::find($id);
            $stmt = $wc->ulb_worflow_id == $request->ulb_workflow_id;
            if ($stmt) {
                return $this->savingWorkflowCandidates($wc, $request);          // Editing Using Trait
            }
            if (!$stmt) {
                $check = WorkflowCandidate::where('ulb_workflow_id', $request->ulb_workflow_id)->first();
                if ($check) {
                    return response()->json('Ulb Workflow Id is already existing', 400);
                }
                if (!$check) {
                    return $this->savingWorkflowCandidates($wc, $request);          // Editing Using Trait
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
