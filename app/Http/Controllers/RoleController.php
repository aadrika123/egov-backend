<?php

namespace App\Http\Controllers;

use App\Repository\Roles\EloquentRoleRepository;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\RoleMenuRequest;
use App\Http\Requests\Roles\UserRoleRequest;
use App\Http\Requests\Roles\RoleMenuLogRequest;
use App\Http\Requests\Roles\RoleUserLogRequest;
use Illuminate\Http\Request;

/**
 * Created By-Anshu Kumar
 * Created On-27-06-2022 
 * 
 * Purpose:- Saving/Editing Role,Menu masters && && Role Menu, Role User && Role Menu, Role User Log masters
 * 
 * Parent Repository-App\Repository\Roles\EloquentRoleRepository
 */
class RoleController extends Controller
{

    // initializing for EloquentRolerepository
    protected $eloquentRole;

    public function __construct(EloquentRoleRepository $eloquentRole)
    {
        $this->EloquentRole = $eloquentRole;
    }

    // Storing Role
    public function storeRole(RoleRequest $request)
    {
        return $this->EloquentRole->roleStore($request);
    }

    // Updating Role
    public function editRole(Request $request, $id)
    {
        return $this->EloquentRole->editRole($request, $id);
    }

    // Get Role
    public function getRole($id)
    {
        return $this->EloquentRole->getRole($id);
    }

    // Get All Roles
    public function getAllRoles()
    {
        return $this->EloquentRole->getAllRoles();
    }

    /*************************************************************************************************** */

    // Storing Role Menu
    public function roleMenu(RoleMenuRequest $request)
    {
        return $this->EloquentRole->menuRole($request);
    }

    // Updating Role Menu
    public function editRoleMenu(RoleMenuRequest $request, $id)
    {
        return $this->EloquentRole->editRoleMenu($request, $id);
    }

    // Getting Role Menus
    public function getRoleMenu($id)
    {
        return $this->EloquentRole->getRoleMenu($id);
    }

    // Getting all Role Menus
    public function getAllRoleMenus()
    {
        return $this->EloquentRole->getAllRoleMenus();
    }
    /**************************************************************************************************** */

    // Storing Role User
    public function roleUser(UserRoleRequest $request)
    {
        return $this->EloquentRole->userRole($request);
    }

    // Updating Role User
    public function editRoleUser(UserRoleRequest $request, $id)
    {
        return $this->EloquentRole->editRoleUser($request, $id);
    }

    // Getting Role User by ID
    public function getRoleUser($id)
    {
        return $this->EloquentRole->getRoleUser($id);
    }

    // Getting all Role Users
    public function getAllRoleUsers()
    {
        return $this->EloquentRole->getAllRoleUsers();
    }

    /***************************************************************************************************** */

    // Storing Role Menu Log
    public function roleMenuLogs(RoleMenuLogRequest $request)
    {
        return $this->EloquentRole->roleMenuLogs($request);
    }

    // Updating Role Menu Logs
    public function editRoleMenuLogs(RoleMenuLogRequest $request, $id)
    {
        return $this->EloquentRole->editRoleMenuLogs($request, $id);
    }

    // Getting Role Menu Logs
    public function getRoleMenuLogs($id)
    {
        return $this->EloquentRole->getRoleMenuLogs($id);
    }

    // Getting all role menu logs
    public function getAllRoleMenuLogs()
    {
        return $this->EloquentRole->getAllRoleMenuLogs();
    }

    /***************************************************************************************************** */

    // Storing Role User Logs
    public function roleUserLogs(RoleUserLogRequest $request)
    {
        return $this->EloquentRole->roleUserLogs($request);
    }

    // Updating Role User logs
    public function editRoleUserLogs(RoleUserLogRequest $request, $id)
    {
        return $this->EloquentRole->editRoleUserLogs($request, $id);
    }

    // Getting Role User Logs
    public function getRoleUserLogs($id)
    {
        return $this->EloquentRole->getRoleUserLogs($id);
    }

    // Getting All Role User Logs
    public function getAllRoleUserLogs()
    {
        return $this->EloquentRole->getAllRoleUserLogs();
    }
}
