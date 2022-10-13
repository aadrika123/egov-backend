<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\MUlbWard;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\MenuUserController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class EloquentMenuWardRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        //validating
        $validateUser = Validator::make(
            $request->all(),
            [
                'UlbId' => 'required',
                'WardName' => 'required',
                'OldWardName' => 'required',
                'UserId' => 'required',
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
            $device = new MUlbWard;
            $device->ulb_id = $request->UlbId;
            $device->ward_name = $request->WardName;
            $device->old_ward_name = $request->OldWardName;
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
        $data = MUlbWard::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = MUlbWard::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request)
    {
        try {
            $device = MUlbWard::find($request->Id);
            $device->ulb_id = $request->UlbId;
            $device->ward_name = $request->WardName;
            $device->old_ward_name = $request->OldWardName;
            $device->user_id = $request->UserId;
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
        $data = MUlbWard::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
