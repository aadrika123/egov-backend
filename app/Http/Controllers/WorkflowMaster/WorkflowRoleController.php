<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar 
 */

class WorkflowRoleController extends Controller
{
    protected $eloquentRole;

    // Initializing Construct function
    public function __construct(iWorkflowRoleRepository $eloquentRole)
    {
        $this->EloquentRole = $eloquentRole;
    }

    //list all roles
    public function index()
    {
        return $this->EloquentRole->list();
    }

    //
    public function create()
    {
    }

    // create new role
    public function store(Request $request)
    {
        return $this->EloquentRole->create($request);
    }

    // list role by id
    public function show($id)
    {
        return $this->EloquentRole->view($id);
    }


    public function edit($id)
    {
        //
    }

    //update role
    public function update(Request $request, $id)
    {
        return $this->EloquentRole->update($request);
    }

    //delete role
    public function destroy($id)
    {
        return $this->EloquentRole->delete($id);
    }
}
