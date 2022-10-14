<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowMasterRepository;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowMasterController extends Controller
{

    protected $eloquentWf;

    // Initializing Construct function
    public function __construct(EloquentWorkflowMasterRepository $eloquentWf)
    {
        $this->EloquentWf = $eloquentWf;
    }

    // list all users in master table
    public function index()
    {
        return $this->EloquentWf->list();
    }


    public function create()
    {
    }

    // creating a new workflow
    public function store(Request $request)
    {
        return $this->EloquentWf->create($request);
    }

    // list workflow by id
    public function show($id)
    {
        return $this->EloquentWf->view($id);
    }


    public function edit($id)
    {
        //
    }

    //update workflow by id
    public function update(Request $request, $id)
    {
        return $this->EloquentWf->update($request);
    }

    //delete workflow
    public function destroy($id)
    {
        return $this->EloquentWf->delete($id);
    }
}
