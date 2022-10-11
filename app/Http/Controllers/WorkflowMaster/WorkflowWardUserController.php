<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowWardUserRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class WorkflowWardUserController extends Controller
{
    protected $eloquentWardUser;
    // Initializing Construct function
    public function __construct(EloquentWorkflowWardUserRepository $eloquentWardUser)
    {
        $this->EloquentWardUser = $eloquentWardUser;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentWardUser->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentWardUser->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentWardUser->delete($id);
    }

    // Updating
    public function update(Request $request)
    {
        return $this->EloquentWardUser->update($request);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentWardUser->view($id);
    }

    // Mapping
    public function getUserByID($id)
    {
        return $this->EloquentWardUser->getUserByID($id);
    }

    public function getUlbByID($id)
    {
        return $this->EloquentWardUser->getUlbByID($id);
    }
}
