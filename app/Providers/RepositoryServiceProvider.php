<?php

namespace App\Providers;

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
