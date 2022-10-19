<?php

namespace App\Providers;

use App\Repository\MenuPermission\Concrete\EloquentMenuGroups;
use App\Repository\MenuPermission\Concrete\EloquentMenuItems;
use App\Repository\MenuPermission\Concrete\EloquentMenuMap;
use App\Repository\MenuPermission\Concrete\EloquentMenuRoles;
use App\Repository\MenuPermission\Concrete\EloquentMenuUlbroles;
use App\Repository\MenuPermission\Interface\iMenuGroupsRepository;
use App\Repository\MenuPermission\Interface\iMenuItemsRepository;
use App\Repository\MenuPermission\Interface\iMenuMapRepository;
use App\Repository\MenuPermission\Interface\iMenuRolesRepository;
use App\Repository\MenuPermission\Interface\iMenuUlbrolesRepository;
use App\Repository\Property\Concrete\EloquentSafRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\ITrade;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\NewConnectionRepository;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\WorkflowMaster\Concrete\WorkflowMasterRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleRepository;
use App\Repository\WorkflowMaster\Concrete\WfWorkflowRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleMapRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleUserMapRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowWardUserRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMappingRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowMasterRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use App\Repository\WorkflowMaster\Interface\iWfWorkflowRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowMappingRepository;


use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * | ------------ Provider for the Binding of Interface and Concrete Class of the Repository --------------------------- |
     * | Created On- 07-10-2022 
     * | Created By- Anshu Kumar
     */
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(iNewConnection::class, NewConnectionRepository::class);
        $this->app->bind(ITrade::class, Trade::class);
        $this->app->bind(iSafRepository::class, EloquentSafRepository::class);
        //menu permission
        $this->app->bind(IMenuGroupsRepository::class, EloquentMenuGroups::class);
        $this->app->bind(IMenuItemsRepository::class, EloquentMenuItems::class);
        $this->app->bind(IMenuMapRepository::class, EloquentMenuMap::class);
        $this->app->bind(IMenuRolesRepository::class, EloquentMenuRoles::class);
        $this->app->bind(IMenuUlbrolesRepository::class, EloquentMenuUlbroles::class);

        // Workflow Master
        $this->app->bind(iWorkflowMappingRepository::class, WorkflowMappingRepository::class);
        $this->app->bind(iWorkflowMasterRepository::class, WorkflowMasterRepository::class);
        $this->app->bind(iWorkflowRoleRepository::class, WorkflowRoleRepository::class);
        $this->app->bind(iWfWorkflowRepository::class, WfWorkflowRepository::class);
        $this->app->bind(iWorkflowRoleMapRepository::class, WorkflowRoleMapRepository::class);
        $this->app->bind(iWorkflowRoleUserMapRepository::class, WorkflowRoleUserMapRepository::class);
        $this->app->bind(iWorkflowWardUserRepository::class, WorkflowWardUserRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
