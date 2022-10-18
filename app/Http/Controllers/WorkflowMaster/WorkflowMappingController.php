<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\WorkflowWardUserRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar 
 */
class WorkflowMappingController extends Controller
{
    protected $eloquentWardUser;

    // Initializing Construct function
    public function __construct(WorkflowWardUserRepository $eloquentWardUser)
    {
        $this->EloquentWardUser = $eloquentWardUser;
    }

    // Mapping
    public function getUserByID($id)
    {
        return $this->EloquentWardUser->getUserByID($id);
    }


    public function getAltNameByUlbId(Request $request)
    {
        return $this->EloquentWardUser->getAltNameByUlbId($request);
    }

    public function getWorkflowNameByUlb(Request $request)
    {
        return $this->EloquentWardUser->getWorkflowNameByUlb($request);
    }

    public function getRoleByUlb(Request $request)
    {
        return $this->EloquentWardUser->getRoleByUlb($request);
    }

    public function getWardByUlb(Request $request)
    {
        return $this->EloquentWardUser->getWardByUlb($request);
    }

    public function getRoleByWorkflowId(Request $request)
    {
        return $this->EloquentWardUser->getRoleByWorkflowId($request);
    }

    public function getUserByRole(Request $request)
    {
        return $this->EloquentWardUser->getUserByRole($request);
    }

    //============================================================
    //============================================================
    public function getRoleByWorkflow(Request $request)
    {
        return $this->EloquentWardUser->getRoleByWorkflow($request);
    }

    public function getUserByWorkflow(Request $request)
    {
        return $this->EloquentWardUser->getUserByWorkflow($request);
    }

    public function getWardsInWorkflow(Request $request)
    {
        return $this->EloquentWardUser->getWardsInWorkflow($request);
    }

    public function getUlbInWorkflow(Request $request)
    {
        return $this->EloquentWardUser->getUlbInWorkflow($request);
    }

    public function getWorkflowByRole(Request $request)
    {
        return $this->EloquentWardUser->getWorkflowByRole($request);
    }

    public function getUserByRoleId(Request $request)
    {
        return $this->EloquentWardUser->getUserByRoleId($request);
    }

    public function getWardByRole(Request $request)
    {
        return $this->EloquentWardUser->getWardByRole($request);
    }

    public function getUlbByRole(Request $request)
    {
        return $this->EloquentWardUser->getUlbByRole($request);
    }

    public function getUserInUlb(Request $request)
    {
        return $this->EloquentWardUser->getUserInUlb($request);
    }

    public function getRoleInUlb(Request $request)
    {
        return $this->EloquentWardUser->getRoleInUlb($request);
    }
}
