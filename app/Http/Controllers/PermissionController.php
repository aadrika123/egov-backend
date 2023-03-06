<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflows\WfPermission;
use App\Models\Workflows\WfRolePermission;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;

/**
 * Controller for giving Controller
 * Created On-06-03-2023 
 * Created By-Mrinal Kumar
 */

class PermissionController extends Controller
{
    /**
     * | add role permission
     */
    public function addPermission(Request $req)
    {
        $mWfPermission = new WfPermission();
        $mWfPermission->addPermission($req);

        return responseMsgs(true, "Permission Added!", '', '010801', '01', '382ms-547ms', 'Post', '');
    }

    /**
     * | giving permission to tje role
     */
    public function addRolePermission(Request $req)
    {
        $rolePermission = new WfRolePermission();
        $rolePermission->addRolePermission($req);

        return responseMsgs(true, "Permission given to the role!", '', '010801', '01', '382ms-547ms', 'Post', '');
    }
}
