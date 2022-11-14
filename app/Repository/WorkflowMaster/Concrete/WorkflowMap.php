<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflow;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;



/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class WorkflowMap implements iWorkflowMapRepository
{
    public function getWorkflownameByWorkflow(Request $request)
    {
        $request->validate([
            'id' => 'required|int'
        ]);
        try {
            $workflow = WfWorkflow::select('workflow_name')
                ->where('wf_workflows.id', $request->id)
                ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
                ->first();
            if ($workflow) {
                return responseMsg(true, "Data Retrived", $workflow);
            }
            return responseMsg(false, "No Data available", "");
        } catch (Exception $e) {
            return $e;
        }
    }
}
