<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfMaster;
use Exception;

/**
 * Controller for Add, Update, View , Delete of Workflow Master Table
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022
 * Created By-Mrinal Kumar
 * Modification On: 19-12-2022
 * Status : Closed
 * -------------------------------------------------------------------------------------------------
 */

class MasterController extends Controller
{

    //create master
    public function createMaster(Request $req)
    {
        try {
            $req->validate([
                'workflowName' => 'required'
            ]);

            $mWfMaster = new WfMaster();
            $mWfMaster->addMaster($req);

            // return responseMsg(true, "Successfully Saved", "");
            return responseMsgs(true, 'Successfully Saved', "", "025811", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025811", 1.0, "", "POST", 400);
        }
    }

    //update master
    public function updateMaster(Request $req)
    {
        try {
            $mWfMaster = new WfMaster();
            $updateMaster  = $mWfMaster->updateMaster($req);

            // return responseMsg(true, "Successfully Updated", $updateMaster);
            return responseMsgs(true, 'Successfully Updated', $updateMaster, "025812", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025812", 1.0, "", "POST", 400);
        }
    }

    //master list by id
    public function masterbyId(Request $req)
    {
        try {

            $mWfMaster = new WfMaster();
            $listById  = $mWfMaster->listbyId($req);

            // return responseMsg(true, "Master List", $listById);
            return responseMsgs(true, 'Master List', $listById, "025813", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025813", 1.0, "", "POST", 400);
        }
    }

    //all master list
    public function getAllMaster()
    {
        try {

            $mWfMaster = new WfMaster();
            $masterList = $mWfMaster->listAllMaster();

            // return responseMsg(true, "All Master List", $masterList);
            return responseMsgs(true, 'All Master List', $masterList, "025814", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025814", 1.0, "", "POST", 400);
        }
    }


    //delete master
    public function deleteMaster(Request $req)
    {
        try {
            $mWfMaster = new WfMaster();
            $mWfMaster->deleteMaster($req);

            // return responseMsg(true, "Data Deleted", '');
            return responseMsgs(true, 'Data Deleted', "", "025815", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025815", 1.0, "", "POST", 400);
        }
    }
}
