<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWfWorkflowRepository;
use Illuminate\Http\Request;
use App\Models\WfWorkflow;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowWorkflowController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class EloquentWfWorkflowRepository implements iWfWorkflowRepository
{

    public function create(Request $request)
    {
        $userId = Auth()->user()->id;

        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'wfMasterId' => 'required',
                'ulbId' => 'required',
                'altName' => 'required',
                'isDocRequired' => 'required',


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
            $device = new WfWorkflow;
            $device->wf_master_id = $request->wfMasterId;
            $device->ulb_id = $request->ulbId;
            $device->alt_name = $request->altName;
            $device->is_doc_required = $request->isDocRequired;
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
        $userId = Auth()->user()->id;
        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'wfMasterId' => 'required',
                'ulbId' => 'required',
                'altName' => 'required',
                'isDocRequired' => 'required',
                'isSuspended' => 'required',
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
            $device = WfWorkflow::find($request->id);
            $device->wf_master_id = $request->wfMasterId;
            $device->ulb_id = $request->ulbId;
            $device->alt_name = $request->altName;
            $device->is_doc_required = $request->isDocRequired;
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
        $data = WfWorkflow::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
