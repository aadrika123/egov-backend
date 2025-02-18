<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflow;
use Exception;

/**
 * Controller for Add, Update, View , Delete of Wf Workflow Table
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022
 * Created By-Mrinal Kumar
 * Modification On: 19-12-2022
 * Status : Open
 * -------------------------------------------------------------------------------------------------
 */

class WorkflowController extends Controller
{
    //mWfWorkFlow master
    public function createWorkflow(Request $req)
    {
        try {
            $req->validate([
                'wfMasterId' => 'required',
                'ulbId' => 'required',
                'altName' => 'required',
                'isDocRequired' => 'required',
            ]);

            $mWfWorkFlow = new WfWorkflow();
            $mWfWorkFlow->addWorkflow($req);

            return responseMsgs(true, "Workflow Saved", "", "025821",1.0,"", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025821", 1.0, "", "POST", 400);
        }
    }

    //update master
    public function updateWorkflow(Request $req)
    {
        try {
            $mWfWorkFlow = new WfWorkflow();
            $updateWorkFlow  = $mWfWorkFlow->updateWorkflow($req);

            return responseMsgs(true, "Successfully Updated", $updateWorkFlow, "025822", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025822", 1.0, "", "POST", 400);
        }
    }

    //master list by id
    public function workflowbyId(Request $req)
    {
        try {

            $mWfWorkFlow = new WfWorkflow();
            $listById  = $mWfWorkFlow->listWfbyId($req);

            return responseMsgs(true, "Workflow List", $listById, "025823", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025823", 1.0, "", "POST", 400);
        }
    }

    //all master list
    public function getAllWorkflow()
    {
        try {

            $mWfWorkFlow = new WfWorkflow();
            $allWorkflow = $mWfWorkFlow->listAllWorkflow();

            return responseMsgs(true, "All Workflow List", $allWorkflow, "025824", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025824", 1.0, "", "POST", 400);
        }
    }


    //delete master
    public function deleteWorkflow(Request $req)
    {
        try {
            $mWfWorkFlow = new WfWorkflow();
            $mWfWorkFlow->deleteWorkflow($req);

            return responseMsgs(true, "Data Deleted", "", "025825", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025825", 1.0, "", "POST", 400);
        }
    }
}
