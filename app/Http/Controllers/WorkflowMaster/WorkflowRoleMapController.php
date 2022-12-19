<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflowrolemap;
use Exception;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowRoleMapController extends Controller
{

    //create master
    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'workflowId' => 'required',
                'wfRoleId' => 'required',
                'forwardRoleId' => 'required',
                'backwardRoleId' => 'required',
            ]);

            $create = new WfWorkflowrolemap();
            $create->addRoleMap($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update master
    public function updateRoleMap(Request $req)
    {
        try {
            $update = new WfWorkflowrolemap();
            $list  = $update->updateRoleMap($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //master list by id
    public function roleMapbyId(Request $req)
    {
        try {

            $listById = new WfWorkflowrolemap();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "Role Map List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all master list
    public function getAllRoleMap()
    {
        try {

            $list = new WfWorkflowrolemap();
            $masters = $list->roleMaps();

            return responseMsg(true, "All Role Map List", $masters);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete master
    public function deleteRoleMap(Request $req)
    {
        try {
            $delete = new WfWorkflowrolemap();
            $delete->deleteRoleMap($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
