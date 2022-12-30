<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfWorkflow extends Model
{
    use HasFactory;


    /**
     * |---------------------------- Get workflow id by workflowId and ulbId -----------------------|
     * | @param workflowID
     * | @param ulbId
     * | @return  
     */
    public function getulbWorkflowId($workflowID, $ulbId)
    {
        return  WfWorkflow::where('wf_master_id', $workflowID)
            ->where('ulb_id', $ulbId)
            ->first();
    }

    //create workflow
    public function addWorkflow($req)
    {
        $createdBy = Auth()->user()->id;
        $data = new WfWorkflow;
        $data->wf_master_id = $req->wfMasterId;
        $data->ulb_id = $req->ulbId;
        $data->alt_name = $req->altName;
        $data->is_doc_required = $req->isDocRequired;
        $data->created_by = $createdBy;
        $data->stamp_date_time = Carbon::now();
        $data->created_at = Carbon::now();
        $data->save();
    }

    //update workflow
    public function updateWorkflow($req)
    {
        $data = WfWorkflow::find($req->id);
        $data->wf_master_id = $req->wfMasterId;
        $data->ulb_id = $req->ulbId;
        $data->alt_name = $req->altName;
        $data->is_doc_required = $req->isDocRequired;
        $data->save();
    }

    //list workflow by id
    public function listbyId($req)
    {
        $data = WfWorkflow::where('id', $req->id)
            ->where('is_suspended', false)
            ->first();
        return $data;
    }

    //All workflow list
    public function listWorkflow()
    {
        $data = WfWorkflow::select('wf_workflows.*', 'wf_masters.workflow_name', 'ulb_masters.ulb_name')
            ->join('wf_masters', 'wf_masters.id', 'wf_workflows.wf_master_id')
            ->join('ulb_masters', 'ulb_masters.id', 'wf_workflows.ulb_id')
            ->where('wf_workflows.is_suspended', false)
            ->orderByDesc('wf_workflows.id')
            ->get();
        return $data;
    }

    /**
     * Delete workflow
     */
    public function deleteWorkflow($req)
    {
        $data = WfWorkflow::find($req->id);
        $data->is_suspended = "true";
        $data->save();
    }
}
