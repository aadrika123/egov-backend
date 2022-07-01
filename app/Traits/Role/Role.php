<?php

namespace App\Traits\Role;

use App\Models\RoleMenu;
use App\Models\RoleUser;
use App\Models\RoleMenuLog;
use App\Models\RoleUserLog;

/**
 * Created On-30-06-2022 
 * Created by- Anshu Kumar
 * --------------------------------------------------------------------------------------------------
 * For Using reusable codes in Role Masters
 */

trait Role
{
    // Success Message
    static public function success()
    {
        return response()->json(['Status' => 'Successfully Saved'], 201);
    }

    // Failure Message for
    static public function failure($a, $b)
    {
        return response()->json(['Status' => false, 'Message' => $a . ' Already Existing For this ' . $b], 400);
    }

    // Message for data not found
    static public function noData()
    {
        return response()->json(['Status' => 'No Data Available'], 404);
    }

    /**
     * Store and Update Role Master
     */
    public function savingRole($role, $request)
    {
        $role->RoleName = $request->RoleName;
        $role->RoleDescription = $request->description;
        $role->Routes = $request->routes;
        $role->save();
        return response()->json(['Successfully' => 'Successfully Saved'], 201);
    }

    // Check Role Menu
    public function checkRoleMenu($request)
    {
        return RoleMenu::where('RoleID', $request->roleId)
            ->where('MenuID', $request->menuId)
            ->first();
    }

    /**
     * Save or Update Role Menu
     */
    public function savingRoleMenu($menu_role, $request)
    {
        $menu_role->RoleID = $request->roleId;
        $menu_role->MenuID = $request->menuId;
        $menu_role->View = $request->view;
        $menu_role->Modify = $request->modify;
        $menu_role->save();
    }

    // Check Role of any Particular User already existing or not
    public function checkUserRole($request)
    {
        return RoleUser::where('UserID', $request->userId)
            ->where('RoleID', $request->roleId)
            ->first();
    }

    // Save Or update Role User
    public function savingRoleUser($role_user, $request)
    {
        $role_user->UserID = $request->userId;
        $role_user->RoleID = $request->roleId;
        $role_user->View = $request->view;
        $role_user->Modify = $request->modify;
        $role_user->save();
    }

    // Check Role Menu Log
    public function checkRoleMenuLog($request)
    {
        return RoleMenuLog::where('RoleID', $request->roleId)
            ->where('MenuID', $request->menuId)
            ->first();
    }

    // Saving Role Menu Logs
    public function savingRoleMenuLog($role_menu_logs, $request)
    {
        $role_menu_logs->RoleID = $request->roleId;
        $role_menu_logs->MenuID = $request->menuId;
        $role_menu_logs->Flag = $request->flag;
        $role_menu_logs->CreatedBy = auth()->user()->id;
        $role_menu_logs->save();
    }

    // Check Role User Log
    public function checkRoleUserLog($request)
    {
        return RoleUserLog::where('UserID', $request->userId)
            ->where('RoleID', $request->roleId)
            ->first();
    }

    // Save or Update Role User Log
    public function savingRoleUserLog($role_user_log, $request)
    {
        $role_user_log->RoleID = $request->roleId;
        $role_user_log->UserID = $request->userId;
        $role_user_log->Flag = $request->flag;
        $role_user_log->CreatedBy = auth()->user()->id;
        $role_user_log->save();
    }
}
