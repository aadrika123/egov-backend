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
use App\Traits\Role\Role;

/**
 * Created By-Anshu Kumar
 * Created On-17-06-2022 
 * 
 * @Parent Controller-App\Http\Controllers\RoleController
 */
class EloquentRoleRepository implements RoleRepository
{
    use Role;
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
            return $this->savingRole($role, $request);          //Trait for Storing Role Master
            return response()->json(['Successfully' => 'Successfully Saved'], 201);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Update Data in role_masters
     * -------------------------------------------
     * @param App\Http\Requests\RoleRequest 
     * @param App\Http\Request\RoleRequest $request
     * @response
     */

    public function editRole(RoleRequest $request, $id)
    {
        try {
            $role = RoleMaster::find($id);
            return $this->savingRole($role, $request);          //Trait for Updating Role Master
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
            $check = $this->checkRoleMenu($request);
            if ($check) {
                return Role::failure('Menu', 'Role');    // Response Message
            }
            // if data is not existing
            if (!$check) {
                $menu_role = new RoleMenu;
                $this->savingRoleMenu($menu_role, $request);           //Trait for Storing Role Menu
                return Role::success();             // Response Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
    /**
     * Editing Role Menus
     * -----------------------------------------------
     * @param App\Http\Requests\RoleMenuRequest 
     * @param App\Http\Request\RoleMenuRequest $request
     * check first if the data already existing or not
     * Update in Database
     * @response 
     */

    public function editRoleMenu(RoleMenuRequest $request, $id)
    {
        try {
            // Checking data already existing 
            $check = $this->checkRoleMenu($request);
            if ($check) {
                return Role::failure('Menu', 'Role');    // Response Message
            }
            // if data is not existing
            if (!$check) {
                $menu_role = RoleMenu::find($id);
                $this->savingRoleMenu($menu_role, $request);           //Trait for updating Role Menu
                return Role::success();                  // Response Message
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
     * @return response
     * Check if the given data already existing in db or not
     * Save
     * @response
     */
    public function userRole(UserRoleRequest $request)
    {
        try {
            // Checking Role of any Particular User already existing or not
            $check = $this->checkUserRole($request);        // Trait for Checking Role User
            if ($check) {
                return Role::failure('Role', 'User');
            }
            // If Role of the user is not existing
            if (!$check) {
                $role_user = new RoleUser();
                $this->savingRoleUser($role_user, $request);   // Trait for Updating Role User
                return Role::success();
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Edit Role Users
     * ---------------------------------------------------
     * @param App\Http\Requests\UserRoleRequest
     * @param App\Http\Requests\UserRoleRequest $request
     * @return response
     * Check if the given data already existing in db or not
     * Updating
     * @response 
     */
    public function editRoleUser(UserRoleRequest $request, $id)
    {
        try {
            // Checking Role of any Particular User already existing or not
            $check = $this->checkUserRole($request);        // Trait for checking Role User
            if ($check) {
                return Role::failure('Role', 'User');
            }
            // If Role of the user is not existing
            if (!$check) {
                $role_user = RoleUser::find($id);
                $this->savingRoleUser($role_user, $request);   // Trait for Updating Role User
                return Role::success();
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
            $check = $this->checkRoleMenuLog($request);
            if ($check) {
                return Role::failure('Menu', 'Role');       // Response Message
            }
            // if data is not existing
            if (!$check) {
                $role_menu_logs = new RoleMenuLog();
                $role_menu_logs->RoleID = $request->roleId;
                $role_menu_logs->MenuID = $request->menuId;
                $role_menu_logs->Flag = $request->flag;
                $role_menu_logs->CreatedBy = auth()->user()->id;
                $role_menu_logs->save();
                return Role::success();                 //Response Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Edit Role Menu Logs
     */
    public function editRoleMenuLogs(RoleMenuLogRequest $request, $id)
    {
        try {
            // Checking data already existing
            $check = $this->checkRoleMenuLog($request);
            if ($check) {
                return Role::failure('Menu', 'Role');                       // Response Message
            }
            // if data is not existing
            if (!$check) {
                $role_menu_logs = RoleMenuLog::find($id);
                $this->savingRoleMenuLog($role_menu_logs, $request);        // Update Using Trait
                return Role::success();                                     //Response Message
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
                return Role::failure('Role', 'User');                       // Failure Message
            }
            // if data already not present
            if (!$check) {
                $role_user_log = new RoleUserLog;
                $role_user_log->RoleID = $request->roleId;
                $role_user_log->UserID = $request->userId;
                $role_user_log->Flag = $request->flag;
                $role_user_log->CreatedBy = auth()->user()->id;
                $role_user_log->save();
                return Role::success();               // Success Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    /**
     * Updating Role User Log
     */
    public function editRoleUserLogs(RoleUserLogRequest $request)
    {
        try {
            // checking data already present in our db 
            $check = $this->checkRoleUserLog($request);
            if ($check) {
                return Role::failure('Role', 'User');                       // Failure Message
            }
            // if data already not present
            if (!$check) {
                $role_user_log = new RoleUserLog;
                $this->savingRoleUserLog($role_user_log, $request);         // Update Role User Log
                return Role::success();                                     // Success Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
