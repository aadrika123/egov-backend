<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
