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


    public function getWorkflownameByWorkflow(Request $request)
    {
        return $this->WfMap->getWorkflownameByWorkflow($request);
    }
}
