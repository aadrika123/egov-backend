<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWfWorkflowRepository;
use Illuminate\Http\Request;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WfWorkflowController extends Controller
{
    protected $eloquentWf;

    // Initializing Construct function
    public function __construct(iWfWorkflowRepository $eloquentWf)
    {
        $this->EloquentWf = $eloquentWf;
    }

    //list all wf workflow
    public function index()
    {
        return $this->EloquentWf->list();
    }


    public function create()
    {
        //
    }

    // create wf workflow
    public function store(Request $request)
    {
        return $this->EloquentWf->create($request);
    }

    // list by wf workflow by id
    public function show($id)
    {
        return $this->EloquentWf->view($id);
    }


    public function edit($id)
    {
        //
    }

    // update wf workflow
    public function update(Request $request, $id)
    {
        return $this->EloquentWf->update($request);
    }

    // delete wf workflow
    public function destroy($id)
    {
        return $this->EloquentWf->delete($id);
    }
}
