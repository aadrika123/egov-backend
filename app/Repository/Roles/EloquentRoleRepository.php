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
use Illuminate\Http\Request;

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
     * @return App\Traits\Roles\Role savingRole 
     */
    public function roleStore(RoleRequest $request)
    {
        try {
            $role = new RoleMaster();
            return $this->savingRole($role, $request);          //Trait for Storing Role Master
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Update Data in role_masters
     * -------------------------------------------
     * @param App\Http\Requests\Request 
     * @param App\Http\Request\Request $request
     * @var stmt If the RoleName is same as previous then update other fields
     * If Role name is not same then check if it is same as others or not then save the fields
     * @return App\Traits\Roles\Role savingRole 
     */

    public function editRole(Request $request, $id)
    {
        try {
            // Validating
            $request->validate([
                'RoleName' => 'required', 'string', 'max:255'
            ]);

            $role = RoleMaster::find($id);
            $stmt = $role->RoleName == $request->RoleName;
            if ($stmt) {
                return $this->savingRole($role, $request);          //Trait for Updating Role Master
            }
            if (!$stmt) {
                // Checking Role Name Already existing or not
                $check = RoleMaster::where('RoleName', '=', $request->RoleName)->first();
                if ($check) {
                    return response()->json(['Status' => false, 'message' => 'Role Name already Existing'], 400);
                }
                if (!$check) {
                    return $this->savingRole($role, $request);          //Trait for Updating Role Master
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Roles by their Respective IDs Table-role_masters
     * --------------------------------------------------------
     */
    public function getRole($id)
    {
        $roles = RoleMaster::find($id);
        if ($roles) {
            return response()->json($roles, 302);
        } else {
            return response()->json(['Status' => 'No Data Available'], 404);
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
     * @var $stmt If the RoleID and MenuID data remain same as Previous and we want to edit only View and Modify the Save the data only
     * check first if the data already existing or not
     * Update in Database
     * @return App\Traits\Roles\Role 
     */

    public function editRoleMenu(RoleMenuRequest $request, $id)
    {
        try {
            $menu_role = RoleMenu::find($id);
            $stmt = $menu_role->RoleID == $request->roleId && $menu_role->MenuID == $request->menuId;
            if ($stmt) {
                $this->savingRoleMenu($menu_role, $request);            //Trait for updating Role Menu
                return Role::success();                                 // Response Message
            }
            if (!$stmt) {
                // Checking data already existing 
                $check = $this->checkRoleMenu($request);
                if ($check) {
                    return Role::failure('Menu', 'Role');    // Response Message
                }
                // if data is not existing
                if (!$check) {
                    $this->savingRoleMenu($menu_role, $request);           //Trait for updating Role Menu
                    return Role::success();                  // Response Message
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Role Menus
     * @return App\Traits\Roles\Role
     */
    public function getRoleMenu($id)
    {
        $role_menu = RoleMenu::find($id);
        if ($role_menu) {
            return response()->json($role_menu, 302);
        } else {
            return Role::noData();                          // Trait
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
     * @param App\Http\Requests\Roles\RoleUserLogRequest $request
     * @var $stmt checks if the RoleID and UserID remains same as previous then update, other fields other not need to check
     * @var !$stmt Checking Data User is already existing for the given Role or Not
     * @return Response
     */
    public function editRoleUser(UserRoleRequest $request, $id)
    {
        try {
            $role_user = RoleUser::find($id);
            $stmt = $role_user->UserID == $request->userId && $role_user->RoleID == $request->roleId;
            if ($stmt) {
                $this->savingRoleUser($role_user, $request);   // Trait for Updating Role User
                return Role::success();
            }
            if (!$stmt) {
                // Checking Role of any Particular User already existing or not
                $check = $this->checkUserRole($request);          // Trait for checking Role User
                if ($check) {
                    return Role::failure('Role', 'User');
                }
                // If Role of the user is not existing
                if (!$check) {

                    $this->savingRoleUser($role_user, $request);   // Trait for Updating Role User
                    return Role::success();
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Role User By ID
     */
    public function getRoleUser($id)
    {
        $role_user = RoleUser::find($id);
        if ($role_user) {
            return response()->json($role_user, 302);
        } else {
            return Role::noData();
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
                return Role::failure('Menu', 'Role');                       // Response Message
            }
            // if data is not existing
            if (!$check) {
                $role_menu_logs = new RoleMenuLog();
                $this->savingRoleMenuLog($role_menu_logs, $request);        // Save Using Trait
                return Role::success();                                     //Response Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Edit Role Menu Logs
     * --------------------------------------------------------
     * @param App\Http\Requests\Roles\RoleMenuLogRequest
     * @param App\Http\Requests\Roles\RoleMenuLogRequest $request
     * @var $stmt checks if the RoleID and MenuID remains same as previous then update only Flag and other No need to check
     * @var !$stmt Checking Data Menu is already existing for the given Role or Not
     * @return Response
     */
    public function editRoleMenuLogs(RoleMenuLogRequest $request, $id)
    {
        try {
            $role_menu_logs = RoleMenuLog::find($id);
            $stmt = $role_menu_logs->RoleID == $request->roleId && $role_menu_logs->MenuID == $request->menuId;
            if ($stmt) {
                $this->savingRoleMenuLog($role_menu_logs, $request);        // Update Using Trait
                return Role::success();                                     //Response Message
            }
            if (!$stmt) {
                // Checking data already existing
                $check = $this->checkRoleMenuLog($request);
                if ($check) {
                    return Role::failure('Menu', 'Role');                       // Response Message
                }
                // if data is not existing
                if (!$check) {
                    $this->savingRoleMenuLog($role_menu_logs, $request);        // Update Using Trait
                    return Role::success();                                     //Response Message
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Role Menu Logs
     * @return App\Traits\Roles\Role
     */
    public function getRoleMenuLogs($id)
    {
        $role_menu_logs = RoleMenuLog::find($id);
        if ($role_menu_logs) {
            return response()->json($role_menu_logs, 302);
        } else {
            return Role::noData();                      // Trait
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
            $check = $this->checkRoleUserLog($request);
            if ($check) {
                return Role::failure('Role', 'User');                       // Failure Message
            }
            // if data already not present
            if (!$check) {
                $role_user_log = new RoleUserLog;
                $this->savingRoleUserLog($role_user_log, $request);         // Save Role User Log
                return Role::success();               // Success Message
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    /**
     * Updating Role User Log
     * @param App\Http\Requests\Roles\RoleUserLogRequest 
     * @param App\Http\Requests\Roles\RoleUserLogRequest $request
     * @var $stmt checks if the RoleID and UserID remains same as previous then update, other fields other not need to check
     * @var !$stmt Checking Data User is already existing for the given Role or Not
     * @return Response
     */
    public function editRoleUserLogs(RoleUserLogRequest $request, $id)
    {
        try {
            $role_user_log = RoleUserLog::find($id);
            $stmt = $role_user_log->UserID == $request->userId && $role_user_log->RoleID == $request->roleId;
            if ($stmt) {
                $this->savingRoleUserLog($role_user_log, $request);         // Update Role User Log
                return Role::success();                                     // Success Message
            }
            if (!$stmt) {
                // checking data already present in our db 
                $check = $this->checkRoleUserLog($request);
                if ($check) {
                    return Role::failure('Role', 'User');                       // Failure Message
                }
                // if data already not present
                if (!$check) {

                    $this->savingRoleUserLog($role_user_log, $request);         // Update Role User Log
                    return Role::success();                                     // Success Message
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Role User Logs data by id
     * @return App\Traits\Roles\Role
     */
    public function getRoleUserLogs($id)
    {
        $role_user_logs = RoleUserLog::find($id);
        if ($role_user_logs) {
            return response()->json($role_user_logs, 302);
        } else {
            return Role::noData();                          // Trait
        }
    }
}
