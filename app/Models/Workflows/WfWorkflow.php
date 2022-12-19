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
        $data->is_suspended = $req->isSuspended;
        $data->save();
    }

    //list workflow by id
    public function listbyId($req)
    {
        $data = WfWorkflow::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
        return $data;
    }

    //All workflow list
    public function listWorkflow()
    {
        $data = WfWorkflow::where('is_suspended', false)
            ->orderByDesc('id')->get();
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
