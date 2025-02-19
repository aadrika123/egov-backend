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
    public function getulbWorkflowId($wfMstId, $ulbId)
    {
        return  WfWorkflow::on('pgsql::read')
            ->where('wf_master_id', $wfMstId)
            ->where('ulb_id', $ulbId)
            ->where('is_suspended', false)
            ->first();
    }

    //create workflow
    public function addWorkflow($req)
    {
        $createdBy = Auth()->user()->id;
        $mWfMaster = new WfWorkflow;
        $mWfMaster->wf_master_id = $req->wfMasterId;
        $mWfMaster->ulb_id = $req->ulbId;
        $mWfMaster->alt_name = $req->altName;
        $mWfMaster->is_doc_required = $req->isDocRequired;
        $mWfMaster->created_by = $createdBy;
        $mWfMaster->initiator_role_id = $req->initiatorRoleId;
        $mWfMaster->finisher_role_id = $req->finisherRoleId;
        $mWfMaster->stamp_date_time = Carbon::now();
        $mWfMaster->created_at = Carbon::now();
        $mWfMaster->save();
    }

    //update workflow
    public function updateWorkflow($req)
    {
        $mWfMaster = WfWorkflow::find($req->id);
        $mWfMaster->wf_master_id = $req->wfMasterId;
        $mWfMaster->ulb_id = $req->ulbId;
        $mWfMaster->alt_name = $req->altName;
        $mWfMaster->is_doc_required = $req->isDocRequired;
        $mWfMaster->initiator_role_id = $req->initiatorRoleId;
        $mWfMaster->finisher_role_id = $req->finisherRoleId;
        $mWfMaster->save();
    }

    //list workflow by id
    public function listWfbyId($req)
    {
        $mWfMaster = WfWorkflow::where('id', $req->id)
            ->where('is_suspended', false)
            ->first();
        return $mWfMaster;
    }

    //All workflow list
    public function listAllWorkflow()
    {
        $mWfMaster = WfWorkflow::select(
            'wf_workflows.*',
            'wf_masters.workflow_name',
            'ulb_masters.ulb_name',
            'wf_roles.role_name as initiator_role_name',
            'frole.role_name as finisher_role_name'
        )
            ->join('wf_masters', 'wf_masters.id', 'wf_workflows.wf_master_id')
            ->join('ulb_masters', 'ulb_masters.id', 'wf_workflows.ulb_id')
            ->leftJoin('wf_roles', 'wf_roles.id', 'wf_workflows.initiator_role_id')
            ->leftJoin('wf_roles as frole', 'frole.id', 'wf_workflows.finisher_role_id')
            ->where('wf_workflows.is_suspended', false)
            ->orderByDesc('wf_workflows.id')
            ->get();
        return $mWfMaster;
    }

    /**
     * Delete workflow
     */
    public function deleteWorkflow($req)
    {
        $mWfMaster = WfWorkflow::find($req->id);
        $mWfMaster->is_suspended = "true";
        $mWfMaster->save();
    }

    /**
     * | Get Wf master id by Workflow id
     */
    public function getWfMstrByWorkflowId($workflowId)
    {
        return WfWorkflow::on('pgsql::read')
            ->select('wf_master_id')
            ->where('id', $workflowId)
            ->firstOrFail();
    }

    /**
     * | Get Wf Dtls
     */
    public function getWfDetails($ulbWorkflowId)
    {
        return WfWorkflow::on('pgsql::read')
            ->findOrFail($ulbWorkflowId);
    }
}
