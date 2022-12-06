<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfMaster;
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


class WorkflowMasterRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;

        //validation 
        $request->validate([
            'workflowName' => 'required|int'
        ]);

        try {
            // create
            $device = new WfMaster;
            $device->workflow_name = $request->workflowName;
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
        $data = WfMaster::where('is_suspended', false)
            ->orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfMaster::find($id);
        $data->is_suspended = "true";
        $data->save();
        if ($data) {
            return response()->json('Successfully Deleted', 200);
        }
        return response()->json('Data Not found', 400);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $createdBy = Auth()->user()->id;
        //validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'workflowName' => 'required',
                'isSuspended' => 'required',
                'workflowName' => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validateUser->errors()
            ], 401);
        }

        try {
            $device = WfMaster::find($id);
            $device->workflow_name = $request->workflowName;
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
        $data = WfMaster::where('id', $id)
            ->where('is_suspended', false)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        }
        return response()->json(['Message' => 'Data not found'], 404);
    }
}
