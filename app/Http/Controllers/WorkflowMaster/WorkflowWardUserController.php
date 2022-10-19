<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;
use Illuminate\Http\Request;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */


class WorkflowWardUserController extends Controller
{
    protected $eloquentWf;

    // Initializing Construct function
    public function __construct(iWorkflowWardUserRepository $eloquentWardUser)
    {
        $this->EloquentWardUser = $eloquentWardUser;
    }

    // list all user
    public function index()
    {
        return $this->EloquentWardUser->list();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    //create
    public function store(Request $request)
    {
        return $this->EloquentWardUser->create($request);
    }

    // list by id
    public function show($id)
    {
        return $this->EloquentWardUser->view($id);
    }

    public function edit($id)
    {
        //
    }

    //update
    public function update(Request $request, $id)
    {
        return $this->EloquentWardUser->update($request);
    }

    //delete
    public function destroy($id)
    {
        return $this->EloquentWardUser->delete($id);
    }

    //Mapping 
    public function getRoleDetails(Request $req)
    {
        return $this->EloquentWardUser->getRoleDetails($req);
    }
}
