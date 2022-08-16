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
        $message = ['status' => true, "message" => "Successfully Saved", "data" => ""];
        return response()->json($message, 200);
    }

    // Failure Message for
    static public function failure($a, $b)
    {
        $message = ['status' => false, 'message' => $a . ' Already Existing For this ' . $b, "data" => ""];
        return response()->json($message, 200);
    }

    // Message for data not found
    static public function noData()
    {
        $message = ['status' => false, 'message' => "Data Not Available", 'data' => ''];
        return response()->json($message, 200);
    }

    /**
     * Store and Update Role Master
     */
    public function savingRole($role, $request)
    {
        $role->role_name = $request->role_name;
        $role->role_description = $request->description;
        $role->routes = $request->routes;
        $role->save();
    }

    // Check Role Menu
    public function checkRoleMenu($request)
    {
        return RoleMenu::where('role_id', $request->roleID)
            ->where('menu_id', $request->menuID)
            ->first();
    }

    /**
     * Save or Update Role Menu
     */
    public function savingRoleMenu($menu_role, $request)
    {
        $menu_role->role_id = $request->roleID;
        $menu_role->menu_id = $request->menuID;
        $menu_role->view = $request->view;
        $menu_role->modify = $request->modify;
        $menu_role->save();
    }

    // Check Role of any Particular User already existing or not
    public function checkUserRole($request)
    {
        return RoleUser::where('user_id', $request->userID)
            ->where('role_id', $request->roleID)
            ->first();
    }

    // Save Or update Role User
    public function savingRoleUser($role_user, $request)
    {
        $role_user->user_id = $request->userID;
        $role_user->role_id = $request->roleID;
        $role_user->view = $request->view;
        $role_user->modify = $request->modify;
        $role_user->save();
    }


    // Check Role Menu Log
    public function checkRoleMenuLog($request)
    {
        return RoleMenuLog::where('role_id', $request->roleID)
            ->where('menu_id', $request->menuID)
            ->first();
    }

    // Saving Role Menu Logs
    public function savingRoleMenuLog($role_menu_logs, $request)
    {
        $role_menu_logs->role_id = $request->roleID;
        $role_menu_logs->menu_id = $request->menuID;
        $role_menu_logs->flag = $request->flag;
        $role_menu_logs->created_by = auth()->user()->id;
        $role_menu_logs->save();
    }

    // Check Role User Log
    public function checkRoleUserLog($request)
    {
        return RoleUserLog::where('user_id', $request->userID)
            ->where('role_id', $request->roleID)
            ->first();
    }

    // Save or Update Role User Log
    public function savingRoleUserLog($role_user_log, $request)
    {
        $role_user_log->role_id = $request->roleID;
        $role_user_log->user_id = $request->userID;
        $role_user_log->flag = $request->flag;
        $role_user_log->created_by = auth()->user()->id;
        $role_user_log->save();
    }
}
