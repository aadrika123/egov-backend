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
        return $this->EloquentWardUser->update($request, $id);
    }

    //delete
    public function destroy($id)
    {
        return $this->EloquentWardUser->delete($id);
    }

    // //Mapping 
    // public function getRoleDetails(Request $req)
    // {
    //     return $this->EloquentWardUser->getRoleDetails($req);
    // }

    // public function getUserById(Request $request)
    // {
    //     return $this->EloquentWardUser->getUserById($request);
    // }

    // public function getWorkflowNameByUlb(Request $request)
    // {
    //     // return 'Hii';
    //     return $this->EloquentWardUser->getWorkflowNameByUlb($request);
    // }

    // public function getRoleByUlb(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleByUlb($request);
    // }

    // public function getWardByUlb(Request $request)
    // {
    //     return $this->EloquentWardUser->getWardByUlb($request);
    // }

    // public function getRoleByWorkflowId(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleByWorkflowId($request);
    // }

    // public function getUserByRole(Request $request)
    // {
    //     return $this->EloquentWardUser->getUserByRole($request);
    // }

    // //============================================================
    // //============================================================
    // public function getRoleByWorkflow(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleByWorkflow($request);
    // }

    // public function getUserByWorkflow(Request $request)
    // {
    //     return $this->EloquentWardUser->getUserByWorkflow($request);
    // }

    // public function getWardsInWorkflow(Request $request)
    // {
    //     return $this->EloquentWardUser->getWardsInWorkflow($request);
    // }

    // public function getUlbInWorkflow(Request $request)
    // {
    //     return $this->EloquentWardUser->getUlbInWorkflow($request);
    // }

    // public function getWorkflowByRole(Request $request)
    // {
    //     return $this->EloquentWardUser->getWorkflowByRole($request);
    // }

    // public function getUserByRoleId(Request $request)
    // {
    //     return $this->EloquentWardUser->getUserByRoleId($request);
    // }

    // public function getWardByRole(Request $request)
    // {
    //     return $this->EloquentWardUser->getWardByRole($request);
    // }

    // public function getUlbByRole(Request $request)
    // {
    //     return $this->EloquentWardUser->getUlbByRole($request);
    // }

    // public function getUserInUlb(Request $request)
    // {
    //     return $this->EloquentWardUser->getUserInUlb($request);
    // }

    // public function getRoleInUlb(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleInUlb($request);
    // }

    // public function getRoleByUserUlbId(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleByUserUlbId($request);
    // }

    // public function getRoleByWardUlbId(Request $request)
    // {
    //     return $this->EloquentWardUser->getRoleByWardUlbId($request);
    // }

    // public function getWorkflownameByWorkfkow(Request $request)
    // {
    //     return $this->EloquentWardUser->getWorkflownameByWorkfkow($request);
    // }
}
