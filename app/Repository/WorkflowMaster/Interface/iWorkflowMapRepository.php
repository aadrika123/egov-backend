<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in WorkflowMappingepository
 * @return ChildRepository App\Repository\WorkflowMaster\WorkflowMapRepository
 */


interface iWorkflowMapRepository
{
    public function getWorkflownameByWorkflow(Request $request);
}
