<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfMaster;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowMasterController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class EloquentWorkflowMasterRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        try {
            // create
            $device = new WfMaster;
            $device->workflow_name = $request->workflowName;
            $device->is_suspended = $request->isSuspended;
            $device->user_id = $request->userId;
            $device->status = $request->status;
            $device->stamp_date_time = $request->stampDateTime;
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
        $data = WfMaster::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfMaster::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = WfMaster::find($request->Id);
            $device->workflow_name = $request->WorkflowName;
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
        $data = WfMaster::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
