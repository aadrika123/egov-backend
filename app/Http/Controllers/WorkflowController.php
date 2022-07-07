<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Workflow\EloquentWorkflowRepository;

/**
 * Controller for Saving Workflows, WorkflowCandidates and all Workflows 
 * Child Repository-App\Repository\Workflow
 * Creation Date-06-07-2022 
 * Created By-Anshu Kumar 
 * ---------------------------------------------------------------------
 * Code Tested By-
 * Code Testing Date-
 */

class WorkflowController extends Controller
{
    /**
     * Initializing for Repository 
     */
    protected $eloquentWorkflow;

    public function __construct(EloquentWorkflowRepository $eloquentWorkflow)
    {
        $this->eloquentWorkflow = $eloquentWorkflow;
    }

    // Storing Workflow
    public function storeWorkflow(Request $request)
    {
        return $this->eloquentWorkflow->storeWorkflow($request);
    }

    // Get Workflow By Id
    public function viewWorkflow($id)
    {
        return $this->eloquentWorkflow->viewWorkflow($id);
    }

    // Update Workflow
    public function updateWorkflow(Request $request, $id)
    {
        return $this->eloquentWorkflow->updateWorkflow($request, $id);
    }

    // Store Workflow Candidates
    public function storeWorkflowCandidate(Request $request)
    {
        return $this->eloquentWorkflow->storeWorkflowCandidate($request);
    }

    // View Workflow Candidates
    public function viewWorkflowCandidates($id)
    {
        return $this->eloquentWorkflow->viewWorkflowCandidates($id);
    }

    // Edit Workflow Candidates
    public function editWorkflowCandidates(Request $request, $id)
    {
        return $this->eloquentWorkflow->editWorkflowCandidates($request, $id);
    }
}
