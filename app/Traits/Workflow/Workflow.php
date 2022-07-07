<?php

namespace App\Traits\Workflow;


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
        $workflow->WorkflowName = $request->WorkflowName;
        $workflow->Initiator = $request->initiator;
        $workflow->Finisher = $request->finisher;
        $workflow->save();
        return response()->json(['Successfully Saved The Workflow'], 200);
    }

    /**
     * Function for Saving and Editing Workflow Candidates
     */
    public function savingWorkflowCandidates($wc, $request)
    {
        $wc->WorkflowID = $request->workflowID;
        $wc->EmployeeID = $request->employeeID;
        $wc->JobDescription = $request->jobDescription;
        $wc->UserID = auth()->user()->id;
        $wc->save();
        return response()->json('Successfully Saved the Workflow Candidates', 200);
    }
}
