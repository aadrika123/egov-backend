<?php

namespace App\Http\Controllers;

use App\Repository\Roles\EloquentRoleRepository;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\RoleMenuRequest;
use App\Http\Requests\Roles\UserRoleRequest;
use App\Http\Requests\Roles\RoleMenuLogRequest;
use App\Http\Requests\Roles\RoleUserLogRequest;

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
    public function editRole(RoleRequest $request, $id)
    {
        return $this->EloquentRole->editRole($request, $id);
    }

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

    // Storing Role User Logs
    public function roleUserLogs(RoleUserLogRequest $request)
    {
        return $this->EloquentRole->roleUserLogs($request);
    }

    // Updating Role User logs
    public function editRoleUserLogs(RoleUserLogRequest $request)
    {
        return $this->EloquentRole->editRoleUserLogs($request);
    }
}
