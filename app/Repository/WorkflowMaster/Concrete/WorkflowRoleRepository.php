<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use Illuminate\Http\Request;
use App\Models\WfRole;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class WorkflowRoleRepository implements iWorkflowRoleRepository
{

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;
        //validating
        $validateUser = Validator::make(
            $request->all(),
            [
                'roleName' => 'required',
                'forwardRoleId' => 'required',
                'backwardRoleId' => 'required',
                'isInitiator' => 'required',
                'isFinisher' => 'required',

            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validateUser->errors()
            ], 401);
        }
        try {
            // create
            $device = new WfRole;
            $device->role_name = $request->roleName;
            $device->forward_role_id = $request->forwardRoleId;
            $device->backward_role_id = $request->backwardRoleId;
            $device->is_initiator = $request->isInitiator;
            $device->is_finisher = $request->isFinisher;
            $device->created_by = $createdBy;
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
        $data = WfRole::where('is_suspended', false)
            ->orderByDesc('id')->get();
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
        $createdBy = Auth()->user()->id;
        //validating
        $validateUser = Validator::make(
            $request->all(),
            [
                'roleName' => 'required',
                'forwardRoleId' => 'required',
                'backwardRoleId' => 'required',
                'isInitiator' => 'required',
                'isFinisher' => 'required',
                'isSuspended' => 'required',
            ]
        );
        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validateUser->errors()
            ], 401);
        }
        try {
            $device = WfRole::find($id);
            $device->role_name = $request->roleName;
            $device->forward_role_id = $request->forwardRoleId;
            $device->backward_role_id = $request->backwardRoleId;
            $device->is_initiator = $request->isInitiator;
            $device->is_finisher = $request->isFinisher;
            $device->is_suspended = $request->isSuspended;
            $device->created_by = $createdBy;
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
        $data = WfRole::where('id', $id)
            ->where('is_suspended', false)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
