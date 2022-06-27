<?php

namespace App\Repository\Roles;

use App\Repository\Roles\RoleRepository;
use Illuminate\Http\Request;
use App\Models\RoleMaster;
use Exception;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\RoleMenuRequest;
use App\Http\Requests\Roles\UserRoleRequest;
use App\Models\RoleMenu;
use App\Models\RoleUser;

/**
 * Created By-Anshu Kumar
 * Created On-17-06-2022 
 * 
 * @Parent Controller-App\Http\Controllers\RoleController
 */
class EloquentRoleRepository implements RoleRepository
{
    /**
     * Storing Data in role_masters 
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
     * storing Role Menus
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
                return response()->json(['Status' => false, 'Message' => 'Menu Already Existing For this Role'], 400);
            }
            // if data is not existing
            if (!$check) {
                $menu_role = new RoleMenu;
                $menu_role->RoleID = $request->roleId;
                $menu_role->MenuID = $request->menuId;
                $menu_role->View = $request->view;
                $menu_role->Modify = $request->modify;
                $menu_role->save();
                return response()->json(['Status' => true, 'Message' => 'Successfully Saved'], 201);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Storing Role Users
     * @param
     * @param
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
                return response()->json(['Status' => false, 'Message' => 'Role Already Existing For this User'], 400);
            }
            // If Role of the user is not existing
            if (!$check) {
                $role_user = new RoleUser();
                $role_user->UserID = $request->userId;
                $role_user->RoleID = $request->roleId;
                $role_user->View = $request->view;
                $role_user->Modify = $request->modify;
                $role_user->save();
                return response()->json(['Status' => 'Successfully Saved'], 201);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
