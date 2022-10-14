<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfMaster;
use App\Models\User;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $userId = Auth()->user()->id;

        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'workflowName' => 'required'
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }
        try {
            // create
            $device = new WfMaster;
            $device->workflow_name = $request->workflowName;
            $device->user_id = $userId;
            $device->stamp_date_time = Carbon::now();
            $device->created_at = Carbon::now();
            $device->save();
            return responseMsg(true, "Successfully Saved", "");
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
    public function update(Request $request)
    {
        $userId = Auth()->user()->id;
        //validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'workflowName' => 'required',
                'isSuspended' => 'required',
                'workflowName' => 'required',
                'status' => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        try {
            $device = WfMaster::find($request->id);
            $device->workflow_name = $request->workflowName;
            $device->is_suspended = $request->isSuspended;
            $device->user_id = $userId;
            $device->status = $request->status;
            $device->stamp_date_time = Carbon::now();
            $device->updated_at = Carbon::now();
            $device->save();
            return responseMsg(true, "Successfully Updated", "");
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
