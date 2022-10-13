<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentMenuWardRepository;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */



class MenuWardController extends Controller
{
    protected $eloquentWard;
    // Initializing Construct function
    public function __construct(EloquentMenuWardRepository $eloquentWard)
    {
        $this->EloquentWard = $eloquentWard;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentWard->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentWard->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentWard->delete($id);
    }

    // Updating
    public function update(Request $request)
    {
        return $this->EloquentWard->update($request);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentWard->view($id);
    }
}
