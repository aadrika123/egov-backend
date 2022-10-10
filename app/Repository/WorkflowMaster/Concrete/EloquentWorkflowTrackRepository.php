<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfTrack;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowTrackControllers
 * -------------------------------------------------------------------------------------------------
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class EloquentWorkflowTrackRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {

        try {
            $checkExisting = WfTrack::where('workflow_id', $request->WorkflowId)
                ->first();
            if ($checkExisting) {
                $checkExisting->workflow_id = $request->WorkflowId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfTrack;
            $device->workflow_id = $request->WorkflowId;
            $device->user_id = $request->UserId;
            $device->tran_time = $request->TranTime;
            $device->ref_key = $request->RefKey;
            $device->ref_id = $request->RefId;
            $device->forward_id = $request->ForwardId;
            $device->waiting_for_citizen = $request->WaitingForCitizen;
            $device->message = $request->Message;
            $device->status = $request->Status;
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
        $data = WfTrack::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfTrack::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = WfTrack::find($request->Id);
            $device->workflow_id = $request->WorkflowId;
            $device->user_id = $request->UserId;
            $device->tran_time = $request->TranTime;
            $device->ref_key = $request->RefKey;
            $device->ref_id = $request->RefId;
            $device->forward_id = $request->ForwardId;
            $device->waiting_for_citizen = $request->WaitingForCitizen;
            $device->message = $request->Message;
            $device->is_suspended = $request->IsSuspended;
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
        $data = WfTrack::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
