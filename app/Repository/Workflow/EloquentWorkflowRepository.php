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
            return response()->json('Successfully Deleted', 200);
        }
    }

    /**
     * Store Workflow Candidates
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * -----------------------------------------------------------------------------------------
     * Business Logic
     * -----------------------------------------------------------------------------------------
     * Validating Each WorkflowID has distinct UserID
     * Store
     * Trait Used-App\Traits\Workflow\Workflow
     * @return response 
     */

    public function storeWorkflowCandidate(Request $request)
    {
        // Validating
        $request->validate([
            'UlbWorkflowID' => 'required|int',
            'UserID' => 'required|int'
        ]);

        try {
            // Checking duplication
            $record = $this->checkExisting($request);
            if ($record) {
                return response()->json('User already existing for this workflow', 400);
            }
            if (!$record) {
                // Storing
                $wc = new WorkflowCandidate;
                return $this->savingWorkflowCandidates($wc, $request);           // Editing Using Trait
            }
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
        $wc = DB::select("select wc.id,
                            wc.ulb_workflow_id,
                            w.workflow_name,
                            wc.user_id,
                            u.user_name as user_name,
                            wc.forward_id,
                            f.user_name as forward_user,
                            wc.backward_id,
                            b.user_name as backward_user,
                            wc.full_movement,
                            wc.is_admin
                        from workflow_candidates wc
                        left join ulb_workflow_masters uw on uw.id=wc.ulb_workflow_id
                        left join workflows w on w.id=uw.workflow_id
                        left join users u on u.id=wc.user_id
                        left join users f on f.id=wc.forward_id
                        left join users b on b.id=wc.backward_id
                        where wc.id=$id");
        if ($wc) {
            return $wc;
        } else {
            return response()->json('Data not Available for this Id', 404);
        }
    }

    /**
     * View All Workflow Candidates
     * 
     */
    public function allWorkflowCandidates()
    {
        $wc = DB::select("select wc.id,
                            wc.ulb_workflow_id,
                            w.workflow_name,
                            wc.user_id,
                            u.user_name as user_name,
                            wc.forward_id,
                            f.user_name as forward_user,
                            wc.backward_id,
                            b.user_name as backward_user,
                            wc.full_movement,
                            wc.is_admin
                        from workflow_candidates wc
                        left join ulb_workflow_masters uw on uw.id=wc.ulb_workflow_id
                        left join workflows w on w.id=uw.workflow_id
                        left join users u on u.id=wc.user_id
                        left join users f on f.id=wc.forward_id
                        left join users b on b.id=wc.backward_id
                        order by wc.id desc");
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
            'UlbWorkflowID' => 'required|int',
            'UserID' => 'required|int'
        ]);

        $wc = WorkflowCandidate::find($id);
        if ($wc) {
            $stmt = $wc->ulb_workflow_id == $request->UlbWorkflowID && $wc->user_id == $request->UserID;
            if ($stmt) {
                return $this->savingWorkflowCandidates($wc, $request);              // Editing Using Trait
            }
            if (!$stmt) {
                // Checking duplication
                $record = $this->checkExisting($request);
                if ($record) {
                    return response()->json('User already existing for this workflow', 400);
                }
                if (!$record) {
                    // Updating
                    return $this->savingWorkflowCandidates($wc, $request);            // Editing Using Trait
                }
            }
        }
        if (!$wc) {
            return response()->json('No data for this Id', 404);
        }
    }

    /**
     * Deleting Workflow Candidates
     */
    public function deleteWorkflowCandidates($id)
    {
        try {
            $wc = WorkflowCandidate::find($id);
            if ($wc == null) {
                return response()->json('Workflow Candidate already Deleted', 400);
            } else {
                $wc->delete();
                return response()->json('Successfully Deleted', 200);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
