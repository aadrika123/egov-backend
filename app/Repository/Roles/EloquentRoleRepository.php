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
     * -------------------------------------------
     * | Request Validate
     * | Check if the role is already existing for the given ulb or not
     * | #check_existance > Checks the given data is already existing on masters or not on Trait function
     */
    public function roleStore(RoleRequest $request)
    {
        try {
            // Check if the role name is already existing or not 
            $check_existing = $this->checkRoleExistance($request);
            if ($check_existing) {
                return responseMsg(false, "Role Is Already Existing for the Ulb", ""); // Response Static Message by Helper function
            }

            $role = new RoleMaster();
            $this->savingRole($role, $request);          //Trait for Storing Role Master
            return responseMsg(true, "Successfully Saved the Role", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Update Data in role_masters
     * -------------------------------------------
     * @param App\Http\Requests\Request 
     * @param App\Http\Request\Request $request
     * -------------------------------------------
     * | #stmt > statement condition for updating the role
     * | #check_existing > Check the already existance of Role and Ulb
     * | @return Response
     */

    public function editRole(RoleRequest $request, $id)
    {

        try {
            $role = RoleMaster::find($id);
            $stmt = $role->role_id == $request->roleID && $role->ulb_id == $request->ulbID;
            if ($stmt) {
                $this->savingRole($role, $request);          //Trait for Storing Role Master
                return responseMsg(true, "Successfully Saved the Role", "");
            }

            // Check if the role name is already existing or not 
            $check_existing = $this->checkRoleExistance($request);
            if ($check_existing) {
                return responseMsg(false, "Role Is Already Existing for the Ulb", ""); // Response Static Message by Helper function
            } else {
                $this->savingRole($role, $request);          //Trait for Storing Role Master
                return responseMsg(true, "Successfully Saved the Role", "");
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Getting Roles by their Respective IDs Table-role_masters and ulb_masters
     * | Fetch Data using fetchRoles Trait
     * ------------------------------------------------------------------------
     */
    public function getRole($id)
    {
        $roles = RoleMaster::where('role_masters.id', $id);
        if ($roles) {
            $data = $this->fetchRoles($roles)->first();
            return responseMsg(true, "Data Fetched", remove_null($data));
        } else {
            return responseMsg(false, "Data Not Found", '');
        }
    }

    /**
     * | Get All Roles by Order By Id Desc
     */
    public function getAllRoles()
    {
        $roles = RoleMaster::orderByDesc('id');
        $data = $this->fetchRoles($roles)->get();
        return responseMsg(true, 'Data Fetched', remove_null($data));
    }

    /**
     * --------------------------------------------------------------------------------------
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
                return Role::failure('Menu', 'Role');                   // Response Message
            }
            // if data is not existing
            if (!$check) {
                $menu_role = new RoleMenu;
                $this->savingRoleMenu($menu_role, $request);           //Trait for Storing Role Menu
                return Role::success();                                 // Response Message
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
            $stmt = $menu_role->role_id == $request->roleID && $menu_role->menu_id == $request->menuID;
            if ($stmt) {
                $this->savingRoleMenu($menu_role, $request);            //Trait for updating Role Menu
                return Role::success();                                 // Response Message
            }
            if (!$stmt) {
                // Checking data already existing 
                $check = $this->checkRoleMenu($request);
                if ($check) {
                    return Role::failure('Menu', 'Role');                   // Response Message
                }
                // if data is not existing
                if (!$check) {
                    $this->savingRoleMenu($menu_role, $request);           //Trait for updating Role Menu
                    return Role::success();                                 // Response Message
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Getting Role Menus
     * @return App\Traits\Roles\Role
     * | remove_null is a helper function which removes null values and add blank value during fetching data
     */
    public function getRoleMenu($id)
    {
        $role_menu = RoleMenu::find($id);
        if ($role_menu) {
            $message = ["status" => false, "message" => "Data Fetched", "data" => remove_null($role_menu)];
            return response()->json($message, 200);
        } else {
            return Role::noData();                          // Trait
        }
    }

    /**
     * | Get All Role Menus
     */

    public function getAllRoleMenus()
    {
        $role_menu = RoleMenu::all();
        $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($role_menu)];
        return response($message, 200);
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
            $stmt = $role_user->user_id == $request->userID && $role_user->role_id == $request->roleID;
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
     * @param $id
     * @return response
     * @return App\Traits\Trait\Role
     */
    public function getRoleUser($id)
    {
        $role_user = RoleUser::find($id);
        if ($role_user) {
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($role_user)];
            return response()->json($message, 200);
        } else {
            return Role::noData();
        }
    }

    /**
     * | Getting all Role Users 
     */
    public function getAllRoleUsers()
    {
        $role_users = RoleUser::orderByDesc('id')->get();
        $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($role_users)];
        return response($message);
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
            $stmt = $role_menu_logs->role_id == $request->roleID && $role_menu_logs->menu_id == $request->menuID;
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
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($role_menu_logs)];
            return response()->json($message, 200);
        } else {
            return Role::noData();                      // Trait
        }
    }

    /**
     * | Get All role menu logs
     */
    public function getAllRoleMenuLogs()
    {
        $logs = RoleMenuLog::orderByDesc("id")->get();
        $message = ["status" => true, "message" => "Data Fetched", "data" => $logs];
        return response($message);
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
            $stmt = $role_user_log->user_id == $request->userID && $role_user_log->role_id == $request->roleID;
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
     * @return response
     * @return App\Traits\Roles\Role
     */
    public function getRoleUserLogs($id)
    {
        $role_user_logs = RoleUserLog::find($id);
        if ($role_user_logs) {
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($role_user_logs)];
            return response()->json($message, 200);
        } else {
            return Role::noData();                          // Trait
        }
    }

    /**
     * | Getting all Role User Logs
     */
    public function getAllRoleUserLogs()
    {
        $logs = RoleUserLog::orderByDesc("id")->get();
        $message = ["status" => true, "message" => "Data Fetched", "data" => $logs];
        return response($message);
    }
}
