<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use Illuminate\Http\Request;
use App\Models\WfRoleusermap;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;


/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleUserMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class EloquentWorkflowRoleUserMapRepository implements iWorkflowRoleUserMapRepository
{

    public function create(Request $request)
    {

        try {
            $checkExisting = WfRoleusermap::where('wf_role_id', $request->WfRoleId)
                ->where('user_id', $request->UserId)
                ->first();
            if ($checkExisting) {
                $checkExisting->wf_role_id = $request->WfRoleId;
                $checkExisting->user_id = $request->UserId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfRoleusermap;
            $device->wf_role_id = $request->WfRoleId;
            $device->user_id = $request->UserId;
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
        $data = WfRoleusermap::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfRoleusermap::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = WfRoleusermap::find($request->Id);
            $device->user_id = $request->UserId;
            $device->ward_id = $request->WardId;
            $device->is_admin = $request->IsAdmin;
            $device->status = $request->Status;
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
        $data = WfRoleusermap::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
