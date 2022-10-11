<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowRoleRepository;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class WorkflowRoleController extends Controller
{
    protected $eloquentRole;
    // Initializing Construct function
    public function __construct(EloquentWorkflowRoleRepository $eloquentRole)
    {
        $this->EloquentRole = $eloquentRole;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentRole->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentRole->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentRole->delete($id);
    }

    // Updating
    public function update(Request $request)
    {
        return $this->EloquentRole->update($request);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentRole->view($id);
    }
}
