<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfWorkflowrolemap extends Model
{
    use HasFactory;

    /**
     * Create Role Map
     */
    public function addRoleMap($req)
    {
        $createdBy = Auth()->user()->id;
        $data = new WfWorkflowrolemap;
        $data->workflow_id = $req->workflowId;
        $data->wf_role_id = $req->wfRoleId;
        $data->forward_role_id = $req->forwardRoleId;
        $data->backward_role_id = $req->backwardRoleId;
        $data->is_initiator = $req->isInitiator;
        $data->is_finisher = $req->isFinisher;
        $data->created_by = $createdBy;
        $data->stamp_date_time = Carbon::now();
        $data->created_at = Carbon::now();
        $data->save();
    }

    /**
     * Update Role Map
     */
    public function updateRoleMap($req)
    {
        $data = WfWorkflowrolemap::find($req->id);
        $data->workflow_id = $req->workflowId;
        $data->wf_role_id = $req->wfRoleId;
        $data->forward_role_id = $req->forwardRoleId;
        $data->backward_role_id = $req->backwardRoleId;
        $data->is_initiator = $req->isInitiator;
        $data->is_finisher = $req->isFinisher;
        $data->is_suspended = $req->isSuspended;
        $data->save();
    }

    /**
     * Role Map List by id
     */
    public function listbyId($req)
    {
        $data = WfWorkflowrolemap::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
        return $data;
    }

    /**
     * All Role Map list
     */
    public function roleMaps()
    {
        $data = WfWorkflowrolemap::where('is_suspended', false)
            ->orderByDesc('id')
            ->get();
        return $data;
    }

    /**
     * Delete Role Map
     */
    public function deleteRoleMap($req)
    {
        $data = WfWorkflowrolemap::find($req->id);
        $data->is_suspended = 'true';
        $data->save();
    }
}
