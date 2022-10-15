<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleMapRepository;
use Illuminate\Http\Request;
use App\Models\WfWorkflowrolemap;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class WorkflowRoleMapRepository implements iWorkflowRoleMapRepository
{

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;

        try {
            $checkExisting = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
                ->where('wf_role_id', $request->wfRoleId)
                ->first();
            if ($checkExisting) {
                $checkExisting->workflow_id = $request->workflowId;
                $checkExisting->wf_role_id = $request->wfRoleId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfWorkflowrolemap;
            $device->workflow_id = $request->workflowId;
            $device->wf_role_id = $request->wfRoleId;
            $device->user_id = $request->userId;
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
        $data = WfWorkflowrolemap::where('is_suspended', false)
            ->orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfWorkflowrolemap::find($id);
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
            $device = WfWorkflowrolemap::find($id);
            $device->workflow_id = $request->workflowId;
            $device->wf_role_id = $request->wfRoleId;
            $device->is_suspended = $request->isSuspended;
            $device->user_id = $request->userId;
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
        $data = WfWorkflowrolemap::where('id', $id)
            ->where('is_suspended', true)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
