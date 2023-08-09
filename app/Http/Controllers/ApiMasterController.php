<?php

namespace App\Http\Controllers;

use App\Repository\Api\EloquentApiRepository;
use App\Http\Requests\Api\ApiStoreRequest;
use App\Http\Requests\Api\ApiSearchRequest;
use Illuminate\Http\Request;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiMasterController extends Controller
{
    /**
     * Controller for api store,api search and api editing
     * ------------------------------------------------------------------------------------------
     * CreatedOn-29-06-2022 
     * CreatedBy-Anshu Kumar
     * ------------------------------------------------------------------------------------------
     * Code Testing
     * Tested By-
     * Feedback-
     * ------------------------------------------------------------------------------------------
     */

    // Initializing Constructor for EloquentApiRepository
    protected $eloquentApi;
    protected $EloquentApi;

    public function __construct(EloquentApiRepository $eloquentApi)
    {
        $this->EloquentApi = $eloquentApi;
    }

    // Storing
    public function store(ApiStoreRequest $request)
    {
        return $this->EloquentApi->store($request);
    }

    // Update
    public function update(ApiStoreRequest $request)
    {
        return $this->EloquentApi->update($request);
    }

    // Get Api By ID
    public function getApiByID($id)
    {
        return $this->EloquentApi->getApiByID($id);
    }

    // Get All Apis
    public function getAllApis()
    {
        return $this->EloquentApi->getAllApis();
    }

    // Search By EndPoint
    public function search(ApiSearchRequest $request)
    {
        return $this->EloquentApi->search($request);
    }

    // Search Api by Tag
    public function searchApiByTag(Request $request)
    {
        return $this->EloquentApi->searchApiByTag($request);
    }


    /**
     * -----------------------------------------------------------------------------------------
     * CreatedOn-06-06-2023
     * CreatedBy-Sandeep Bara
     * ------------------------------------------------------------------------------------------
     * Code Testing
     * Tested By-
     * Feedback-
     * ------------------------------------------------------------------------------------------
     */

    # Menu-Api-map curde 

    public function getRowApiList(Request $request)
    {
        try{ 
            $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(function ($route) {  
                return [
                    'Prefix'=>explode("/",$route->getPrefix())[0]??"",
                    'Module'=>explode("/",$route->getPrefix())[1]??"",
                    'Method' => implode('|', $route->methods()),
                    'URI' => $route->uri(),
                    'Name' => $route->getName(),
                    'Action' => ltrim($route->getActionName(), '\\'),
                    'Middleware' => implode(', ', $route->gatherMiddleware()),
                ];
            });
            $routes =  $routes->where("Prefix","api")->values();
            if($request->Module)
            {
                $routes =  $routes->where("Module",$request->Module)->values();
            }

            return responseMsgs(true,"data Fetched",remove_null($routes));
        }
        catch(Exception $e)
        {
            return responseMsgs(false,"data Not Fetched","");
        }
        
    }
    
}
