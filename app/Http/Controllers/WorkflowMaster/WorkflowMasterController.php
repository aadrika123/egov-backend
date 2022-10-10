<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowMasterRepository;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class WorkflowMasterController extends Controller
{
    protected $eloquentWf;
    // Initializing Construct function
    public function __construct(EloquentWorkflowMasterRepository $eloquentWf)
    {
        $this->EloquentWf = $eloquentWf;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentWf->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentWf->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentWf->delete($id);
    }

    // Updating
    public function update(Request $request, $id)
    {
        return $this->EloquentWf->update($request, $id);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentWf->view($id);
    }
}
