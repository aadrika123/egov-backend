<?php

namespace App\Providers;

use App\Repository\MenuPermission\Concrete\EloquentMenuGroups;
use App\Repository\MenuPermission\Concrete\EloquentMenuItems;
use App\Repository\MenuPermission\Concrete\EloquentMenuMap;
use App\Repository\MenuPermission\Concrete\EloquentMenuRoles;
use App\Repository\MenuPermission\Concrete\EloquentMenuUlbroles;
use App\Repository\MenuPermission\Interface\IMenuGroupsRepository;
use App\Repository\MenuPermission\Interface\IMenuItemsRepository;
use App\Repository\MenuPermission\Interface\IMenuMapRepository;
use App\Repository\MenuPermission\Interface\IMenuRolesRepository;
use App\Repository\MenuPermission\Interface\IMenuUlbrolesRepository;
use App\Repository\Property\Concrete\EloquentSafRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\ITrade;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\NewConnectionRepository;
use App\Repository\Water\Interfaces\iNewConnection;
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
        $this->app->bind(IMenuGroupsRepository::class,EloquentMenuGroups::class);
        $this->app->bind(IMenuItemsRepository::class,EloquentMenuItems::class);
        $this->app->bind(IMenuMapRepository::class,EloquentMenuMap::class);
        $this->app->bind(IMenuRolesRepository::class,EloquentMenuRoles::class);
        $this->app->bind(IMenuUlbrolesRepository::class,EloquentMenuUlbroles::class);
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
