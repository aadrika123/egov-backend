<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleMapRepository;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowRoleMapController extends Controller
{
    protected $eloquentRoleMap;
    // Initializing Construct function

    public function __construct(iWorkflowRoleMapRepository $eloquentRoleMap)
    {
        $this->EloquentRoleMap = $eloquentRoleMap;
    }

    //list all rolemap
    public function index()
    {
        return $this->EloquentRoleMap->list();
    }


    public function create()
    {
        //
    }

    // create 
    public function store(Request $request)
    {
        return $this->EloquentRoleMap->create($request);
    }

    //
    public function show($id)
    {
        return $this->EloquentRoleMap->view($id);
    }

    public function edit($id)
    {
        //
    }

    // update
    public function update(Request $request, $id)
    {
        return $this->EloquentRoleMap->update($request, $id);
    }

    //delete
    public function destroy($id)
    {
        return $this->EloquentRoleMap->delete($id);
    }
}
