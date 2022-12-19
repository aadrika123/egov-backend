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
        $data = new WfMaster;
        $data->workflow_name = $req->workflowName;
        $data->created_by = $createdBy;
        $data->stamp_date_time = Carbon::now();
        $data->created_at = Carbon::now();
        $data->save();
    }

    ///update master list
    public function updateMaster($req)
    {
        $data = WfMaster::find($req->id);
        $data->workflow_name = $req->workflowName;
        $data->is_suspended = $req->isSuspended;
        $data->save();
    }


    //list by id
    public function listById($req)
    {
        $list = WfMaster::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
        return $list;
    }

    //all master list
    public function listMaster()
    {
        $list = WfMaster::where('is_suspended', false)
            ->orderByDesc('id')
            ->get();
        return $list;
    }


    //delete master
    public function deleteMaster($req)
    {
        $data = WfMaster::find($req->id);
        $data->status = 0;
        $data->is_suspended = "true";
        $data->save();
    }
}
