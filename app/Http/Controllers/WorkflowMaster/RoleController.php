<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRole;
use Exception;

class RoleController extends Controller
{
    //create master
    public function createRole(Request $req)
    {
        try {
            $mWfRole = new WfRole();
            $mWfRole->addRole($req);

            return responseMsgs(true, "Successfully Saved", "", "025831", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025831", 1.0, "", "POST", 400);
        }
    }

    //update master
    public function updateRole(Request $req)
    {
        try {
            $mWfRole = new WfRole();
            $updateRole  = $mWfRole->updateRole($req);

            return responseMsgs(true, "Successfully Updated", $updateRole, "025832", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025832", 1.0, "", "POST", 400);
        }
    }

    //master list by id
    public function getRole(Request $req)
    {
        try {

            $mWfRole = new WfRole();
            $getRolelist  = $mWfRole->rolebyId($req);

            return responseMsgs(true, "Role List", $getRolelist, "025833", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025833", 1.0, "", "POST", 400);
        }
    }

    //all master list
    public function getAllRoles()
    {
        try {

            $mWfRole = new WfRole();
            $allMastersList = $mWfRole->roleList();

            return responseMsgs(true, "All Role List", $allMastersList, "025834", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025834", 1.0, "", "POST", 400);
        }
    }


    //delete master
    public function deleteRole(Request $req)
    {
        try {
            $mWfRole = new WfRole();
            $mWfRole->deleteRole($req);

            return responseMsgs(true, "Data Deleted", '', "025835", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025832", 1.0, "", "POST", 400);
        }
    }
}
