<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleMapRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */



class WorkflowRoleMapController extends Controller
{
    protected $eloquentRoleMap;
    // Initializing Construct function
    public function __construct(EloquentWorkflowRoleMapRepository $eloquentRoleMap)
    {
        $this->EloquentRoleMap = $eloquentRoleMap;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentRoleMap->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentRoleMap->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentRoleMap->delete($id);
    }

    // Updating
    public function update(Request $request)
    {
        return $this->EloquentRoleMap->update($request);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentRoleMap->view($id);
    }
}
