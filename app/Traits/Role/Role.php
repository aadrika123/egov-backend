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
        $role->role_name = $request->role_name;
        $role->role_description = $request->Description;
        $role->routes = $request->Routes;
        $role->save();
        return response()->json(['Successfully' => 'Successfully Saved'], 201);
    }

    // Check Role Menu
    public function checkRoleMenu($request)
    {
        return RoleMenu::where('role_id', $request->RoleID)
            ->where('menu_id', $request->MenuID)
            ->first();
    }

    /**
     * Save or Update Role Menu
     */
    public function savingRoleMenu($menu_role, $request)
    {
        $menu_role->role_id = $request->RoleID;
        $menu_role->menu_id = $request->MenuID;
        $menu_role->view = $request->View;
        $menu_role->modify = $request->Modify;
        $menu_role->save();
    }

    // Check Role of any Particular User already existing or not
    public function checkUserRole($request)
    {
        return RoleUser::where('user_id', $request->UserID)
            ->where('role_id', $request->RoleID)
            ->first();
    }

    // Save Or update Role User
    public function savingRoleUser($role_user, $request)
    {
        $role_user->user_id = $request->UserID;
        $role_user->role_id = $request->RoleID;
        $role_user->view = $request->View;
        $role_user->modify = $request->Modify;
        $role_user->save();
    }

    // Check Role Menu Log
    public function checkRoleMenuLog($request)
    {
        return RoleMenuLog::where('role_id', $request->RoleID)
            ->where('menu_id', $request->MenuID)
            ->first();
    }

    // Saving Role Menu Logs
    public function savingRoleMenuLog($role_menu_logs, $request)
    {
        $role_menu_logs->role_id = $request->RoleID;
        $role_menu_logs->menu_id = $request->MenuID;
        $role_menu_logs->flag = $request->Flag;
        $role_menu_logs->created_by = auth()->user()->id;
        $role_menu_logs->save();
    }

    // Check Role User Log
    public function checkRoleUserLog($request)
    {
        return RoleUserLog::where('user_id', $request->UserID)
            ->where('role_id', $request->RoleID)
            ->first();
    }

    // Save or Update Role User Log
    public function savingRoleUserLog($role_user_log, $request)
    {
        $role_user_log->role_id = $request->RoleID;
        $role_user_log->user_id = $request->UserID;
        $role_user_log->flag = $request->Flag;
        $role_user_log->created_by = auth()->user()->id;
        $role_user_log->save();
    }
}
