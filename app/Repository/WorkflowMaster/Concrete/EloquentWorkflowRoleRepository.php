<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfRole;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class EloquentWorkflowRoleRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        try {
            // create
            $device = new WfRole;
            $device->role_name = $request->RoleName;
            $device->forward_user_id = $request->ForwardUserId;
            $device->backward_user_id = $request->BackwardUserId;
            $device->is_initiator = $request->IsInitiator;
            $device->is_finisher = $request->IsFinisher;
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
        $data = WfRole::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfRole::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = WfRole::find($request->Id);
            $device->role_name = $request->RoleName;
            $device->forward_user_id = $request->ForwardUserId;
            $device->backward_user_id = $request->BackwardUserId;
            $device->is_initiator = $request->IsInitiator;
            $device->is_finisher = $request->IsFinisher;
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
        $data = WfRole::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
