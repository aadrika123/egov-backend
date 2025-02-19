<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfMaster extends Model
{
    use HasFactory;
    public $timestamps = false;


    //create master
    public function addMaster($req)
    {
        $createdBy = Auth()->user()->id;
        $mWfMaster = new WfMaster;
        $mWfMaster->workflow_name = $req->workflowName;
        $mWfMaster->created_by = $createdBy;
        $mWfMaster->module_id = $req->moduleId;
        $mWfMaster->stamp_date_time = Carbon::now();
        $mWfMaster->created_at = Carbon::now();
        $mWfMaster->save();
    }

    ///update master list
    public function updateMaster($req)
    {
        $mWfMaster = WfMaster::find($req->id);
        $mWfMaster->workflow_name = $req->workflowName;
        $mWfMaster->module_id = $req->moduleId;
        $mWfMaster->save();
    }


    //list by id
    public function listById($req)
    {
        $mWfMaster = WfMaster::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
        return $mWfMaster;
    }

    //all master list
    public function listAllMaster()
    {
        $mWfMaster = WfMaster::select(
            'wf_masters.id',
            'workflow_name',
            'module_name',
            'module_id'
        )
            ->leftJoin('module_masters', 'module_masters.id', 'wf_masters.module_id')
            ->where('wf_masters.is_suspended', false)
            ->orderByDesc('wf_masters.id')
            ->get();
        return $mWfMaster;
    }


    //delete master
    public function deleteMaster($req)
    {
        $mWfMaster = WfMaster::find($req->id);
        $mWfMaster->is_suspended = "true";
        $mWfMaster->save();
    }
}
