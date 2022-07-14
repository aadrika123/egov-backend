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
        $workflow = Workflow::find($id);
        if ($workflow) {
            return response()->json($workflow, 200);
        } else {
            return response()->json('Workflow Not Available for this id', 404);
        }
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
            'workflowID' => 'required|int',
            'employeeID' => 'required|int'
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
        $wc = DB::table('workflow_candidates')
            ->leftJoin('users', 'workflow_candidates.CreatedBy', '=', 'users.id')
            ->leftJoin('workflows', 'workflow_candidates.WorkflowID', '=', 'workflows.id')
            ->select(
                'workflow_candidates.id',
                'workflows.WorkflowName',
                'workflow_candidates.JobDescription',
                'workflow_candidates.ForwardID',
                'workflow_candidates.BackwardID',
                'workflow_candidates.FullMovement',
                'workflow_candidates.IsAdmin',
                'users.UserName as CreatedBy'
            )
            ->first();

        if ($wc) {
            return response()->json($wc, 200);
        } else {
            return response()->json('Data not Found', 404);
        }
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
            'workflowID' => 'required|int',
            'employeeID' => 'required|int'
        ]);

        try {
            $wc = WorkflowCandidate::find($id);
            if ($wc) {
                return $this->savingWorkflowCandidates($wc, $request);          // Editing Using Trait
            } else {
                return response()->json(['Data Not Found for this id'], 400);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
