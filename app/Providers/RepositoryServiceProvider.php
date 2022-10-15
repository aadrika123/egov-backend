<?php

namespace App\Providers;

use App\Repository\Property\Concrete\EloquentSafRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\ITrade;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\NewConnectionRepository;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowMasterRepository;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleRepository;
use App\Repository\WorkflowMaster\Concrete\EloquentWfWorkflowRepository;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleMapRepository;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleUserMapRepository;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowWardUserRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowMasterRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use App\Repository\WorkflowMaster\Interface\iWfWorkflowRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;

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

        // Workflow Master
        $this->app->bind(iWorkflowMasterRepository::class, EloquentWorkflowMasterRepository::class);
        $this->app->bind(iWorkflowRoleRepository::class, EloquentWorkflowRoleRepository::class);
        $this->app->bind(iWfWorkflowRepository::class, EloquentWfWorkflowRepository::class);
        $this->app->bind(iWorkflowRoleMapRepository::class, EloquentWorkflowRoleMapRepository::class);
        $this->app->bind(iWorkflowRoleUserMapRepository::class, EloquentWorkflowRoleUserMapRepository::class);
        $this->app->bind(iWorkflowWardUserRepository::class, EloquentWorkflowWardUserRepository::class);
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
