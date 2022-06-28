<?php

namespace App\Repository\Roles;

use App\Repository\Roles\RoleRepository;
use Exception;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\RoleMenuRequest;
use App\Http\Requests\Roles\UserRoleRequest;
use App\Http\Requests\Roles\RoleMenuLogRequest;
use App\Http\Requests\Roles\RoleUserLogRequest;
use App\Models\RoleMaster;
use App\Models\RoleMenu;
use App\Models\RoleMenuLog;
use App\Models\RoleUser;
use App\Models\RoleUserLog;
use App\Traits\Role\MenuRole;
use App\Traits\Role\UserRole;

/**
 * Created By-Anshu Kumar
 * Created On-17-06-2022 
 * 
 * @Parent Controller-App\Http\Controllers\RoleController
 */
class EloquentRoleRepository implements RoleRepository
{
    use MenuRole, UserRole;
    /**
     * -------------------------------------------
     * Storing Data in role_masters 
     * -------------------------------------------
     * @param App\Http\Requests\RoleRequest 
     * @param App\Http\Request\RoleRequest $request
     * @response 
     */
    public function roleStore(RoleRequest $request)
    {
        try {
            $role = new RoleMaster();
            $role->RoleName = $request->RoleName;
            $role->RoleDescription = $request->description;
            $role->Routes = $request->routes;
            $role->save();
            return response()->json(['Successfully' => 'Successfully Saved'], 201);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * --------------------------------------------
     * storing Role Menus
     * --------------------------------------------
     * @param App\Http\Requests\RoleMenuRequest 
     * @param App\Http\Request\RoleMenuRequest $request
     * check first if the data already existing or not
     * Save in Database
     * @response 
     */

    public function menuRole(RoleMenuRequest $request)
    {
        try {
            // Checking data already existing 
            $check = RoleMenu::where('RoleID', $request->roleId)
                ->where('MenuID', $request->menuId)
                ->first();
            if ($check) {
                return MenuRole::falseRoleMenuLog();    // Response Message
            }
            // if data is not existing
            if (!$check) {
                $menu_role = new RoleMenu;
                $menu_role->RoleID = $request->roleId;
                $menu_role->MenuID = $request->menuId;
                $menu_role->View = $request->view;
                $menu_role->Modify = $request->modify;
                $menu_role->save();
                return MenuRole::success();             // Response Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * ---------------------------------------------
     * Storing Role Users
     * ---------------------------------------------
     * @param App\Http\Requests\UserRoleRequest
     * @param App\Http\Requests\UserRoleRequest $request
     * @return response()
     * Check if the given data already existing in db or not
     * Save
     * @response
     */
    public function userRole(UserRoleRequest $request)
    {
        try {
            // Checking Role of any Particular User already existing or not
            $check = RoleUser::where('UserID', $request->userId)
                ->where('RoleID', $request->roleId)
                ->first();
            if ($check) {
                return UserRole::failure();
            }
            // If Role of the user is not existing
            if (!$check) {
                $role_user = new RoleUser();
                $role_user->UserID = $request->userId;
                $role_user->RoleID = $request->roleId;
                $role_user->View = $request->view;
                $role_user->Modify = $request->modify;
                $role_user->save();
                return UserRole::userRoleSuccess();
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * -----------------------------------------------
     * Storing Role Menu Logs
     * -----------------------------------------------
     * @param App\Http\Requests\Roles\RoleMenuLogRequest
     * @param App\Http\Requests\Roles\RoleMenuLogRequest $request
     * Checking Data Menu is already existing for the given Role or Not
     * @return Response
     */

    public function roleMenuLogs(RoleMenuLogRequest $request)
    {
        try {
            // Checking data already existing
            $check = RoleMenuLog::where('RoleID', $request->roleId)
                ->where('MenuID', $request->menuId)
                ->first();
            if ($check) {
                return MenuRole::falseRoleMenuLog();       // Response Message
            }
            // if data is not existing
            if (!$check) {
                $role_menu_logs = new RoleMenuLog();
                $role_menu_logs->RoleID = $request->roleId;
                $role_menu_logs->MenuID = $request->menuId;
                $role_menu_logs->Flag = $request->flag;
                $role_menu_logs->CreatedBy = auth()->user()->id;
                $role_menu_logs->save();
                return MenuRole::success();                 //Response Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * -------------------------------------------------
     * Storing Role User Log 
     * -------------------------------------------------
     * @param App\Http\Requests\Roles\RoleUserLogRequest 
     * @param App\Http\Requests\Roles\RoleUserLogRequest $request
     * Checking Data User is already existing for the given Role or Not
     * @return Response
     * 
     */
    public function roleUserLogs(RoleUserLogRequest $request)
    {
        // checking data already present in our db 
        try {
            $check = RoleUserLog::where('UserID', $request->userId)
                ->where('RoleID', $request->roleId)
                ->first();
            if ($check) {
                return UserRole::failure();                       // Failure Message
            }
            // if data already not present
            if (!$check) {
                $role_user_log = new RoleUserLog;
                $role_user_log->RoleID = $request->roleId;
                $role_user_log->UserID = $request->userId;
                $role_user_log->Flag = $request->flag;
                $role_user_log->CreatedBy = auth()->user()->id;
                $role_user_log->save();
                return UserRole::userRoleSuccess();               // Success Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
