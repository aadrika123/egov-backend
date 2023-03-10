<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflows\WfPermission;
use App\Models\Workflows\WfRolePermission;
use App\Models\Workflows\WfRoleusermap;
use App\Pipelines\ModulePermissions;
use Exception;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

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

    /**
     * | Get Permission by User
     */
    public function getUserPermission(Request $req)
    {
        $req->validate([
            'module' => 'required'
        ]);
        try {
            $userId = auth()->user()->id;
            $mWfRoleUserMap = new WfRoleusermap();
            $wfRoles = $mWfRoleUserMap->getRoleIdByUserId($userId);
            $roleIds = collect($wfRoles)->map(function ($item) {
                return $item->wf_role_id;
            });
            $permissions = app(Pipeline::class)
                ->send(WfRolePermission::query()->whereIn('wf_role_permissions.wf_role_id', $roleIds)->where('p.status', true))
                ->through([
                    ModulePermissions::class,
                ])
                ->thenReturn()
                ->get();
            return responseMsgs(true, "Permissions", remove_null($permissions), '100101', '1.0', '', 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), '', '100101', '1.0', '', 'POST', $req->deviceId ?? "");
        }
    }
}
