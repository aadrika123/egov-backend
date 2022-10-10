<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfWorkflow;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowWorkflowController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class EloquentWorkflowWorkflowRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        try {
            // create
            $device = new WfWorkflow;
            $device->wf_master_id = $request->WfMasterId;
            $device->ulb_id = $request->UlbId;
            $device->alt_name = $request->AltName;
            $device->is_doc_required = $request->IsDocRequired;
            $device->is_suspended = $request->IsSuspended;
            $device->user_id = $request->UserId;
            $device->status = $request->Status;
            $device->stamp_date_time = $request->StampDateTime;
            $device->save();
            return response()->json(['Status' => 'Successfully Saved'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    /**
     * GetAll data
     */
    public function list()
    {
        $data = WfWorkflow::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfWorkflow::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = new WfWorkflow;
            $device->wf_master_id = $request->WfMasterId;
            $device->ulb_id = $request->UlbId;
            $device->alt_name = $request->AltName;
            $device->is_doc_required = $request->IsDocRequired;
            $device->is_suspended = $request->IsSuspended;
            $device->user_id = $request->UserId;
            $device->status = $request->Status;
            $device->stamp_date_time = $request->StampDateTime;
            $device->save();
            return response()->json(['Status' => 'Successfully Updated'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * list view by IDs
     */

    public function view($id)
    {
        $data = WfWorkflow::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
