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



class WorkflowRoleUserMapRepository implements iWorkflowRoleUserMapRepository
{

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;

        try {
            $checkExisting = WfRoleusermap::where('wf_role_id', $request->wfRoleId)
                ->where('user_id', $request->userId)
                ->first();
            if ($checkExisting) {
                $checkExisting->wf_role_id = $request->wfRoleId;
                $checkExisting->user_id = $request->userId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfRoleusermap;
            $device->wf_role_id = $request->wfRoleId;
            $device->user_id = $request->userId;
            $device->createdBy = $createdBy;
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
        $data = WfRoleusermap::where('is_suspended', false)
            ->orderByDesc('id')->get();
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
        $createdBy = Auth()->user()->id;

        try {
            $device = WfRoleusermap::find($id);
            $device->ward_id = $request->wardId;
            $device->user_id = $request->userId;
            $device->createdBy = $createdBy;
            $device->is_admin = $request->isAdmin;
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
        $data = WfRoleusermap::where('id', $id)
            ->where('is_suspended', true)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
