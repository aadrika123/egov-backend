<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleUserMapRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class WorkflowRoleUserMapController extends Controller
{
    protected $eloquentRoleUserMap;
    // Initializing Construct function
    public function __construct(EloquentWorkflowRoleUserMapRepository $eloquentRoleUserMap)
    {
        $this->EloquentRoleUserMap = $eloquentRoleUserMap;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentRoleUserMap->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentRoleUserMap->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentRoleUserMap->delete($id);
    }

    // Updating
    public function update(Request $request, $id)
    {
        return $this->EloquentRoleUserMap->update($request, $id);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentRoleUserMap->view($id);
    }
}
