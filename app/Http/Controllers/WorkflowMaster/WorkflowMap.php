<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;


class WorkflowMap extends Controller
{
    protected $wfMap;
    // Initializing Construct function
    public function __construct(iWorkflowMapRepository $wfMap)
    {
        $this->WfMap = $wfMap;
    }

    //Mapping 
    public function getRoleDetails(Request $req)
    {
        return $this->WfMap->getRoleDetails($req);
    }

    public function getUserById(Request $request)
    {
        return $this->WfMap->getUserById($request);
    }

    public function getWorkflowNameByUlb(Request $request)
    {
        // return 'Hii';
        return $this->WfMap->getWorkflowNameByUlb($request);
    }

    public function getRoleByUlb(Request $request)
    {
        return $this->WfMap->getRoleByUlb($request);
    }

    public function getWardByUlb(Request $request)
    {
        return $this->WfMap->getWardByUlb($request);
    }

    public function getUserByRole(Request $request)
    {
        return $this->WfMap->getUserByRole($request);
    }

    //============================================================
    //============================================================
    public function getRoleByWorkflow(Request $request)
    {
        return $this->WfMap->getRoleByWorkflow($request);
    }

    public function getUserByWorkflow(Request $request)
    {
        return $this->WfMap->getUserByWorkflow($request);
    }

    public function getWardsInWorkflow(Request $request)
    {
        return $this->WfMap->getWardsInWorkflow($request);
    }

    public function getUlbInWorkflow(Request $request)
    {
        return $this->WfMap->getUlbInWorkflow($request);
    }

    public function getWorkflowByRole(Request $request)
    {
        return $this->WfMap->getWorkflowByRole($request);
    }

    public function getUserByRoleId(Request $request)
    {
        return $this->WfMap->getUserByRoleId($request);
    }

    public function getWardByRole(Request $request)
    {
        return $this->WfMap->getWardByRole($request);
    }

    public function getUlbByRole(Request $request)
    {
        return $this->WfMap->getUlbByRole($request);
    }

    public function getUserInUlb(Request $request)
    {
        return $this->WfMap->getUserInUlb($request);
    }

    public function getRoleInUlb(Request $request)
    {
        return $this->WfMap->getRoleInUlb($request);
    }

    public function getRoleByUserUlbId(Request $request)
    {
        return $this->WfMap->getRoleByUserUlbId($request);
    }

    public function getRoleByWardUlbId(Request $request)
    {
        return $this->WfMap->getRoleByWardUlbId($request);
    }

    //
    public function getWorkflownameByWorkflow(Request $request)
    {
        return $this->WfMap->getWorkflownameByWorkflow($request);
    }
}
