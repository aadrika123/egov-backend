<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\MUser;
use Carbon\Carbon;
use Exception;
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



class EloquentMenuUserRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        $userId = Auth()->user()->id;
        //validating
        $validateUser = Validator::make(
            $request->all(),
            [
                'email' => 'required',
                'fullName' => 'required',
                'userName' => 'required',
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
            $device = new MUser;
            $device->ulb_id = $request->ulbId;
            $device->citizen_id = $request->citizenId;
            $device->employee_id = $request->employeeId;
            $device->vendor_id = $request->vendorId;
            $device->agency_id = $request->agencyId;
            $device->is_admin = $request->isAdmin;
            $device->is_psudo = $request->isPsudo;
            $device->email = $request->email;
            $device->user_name = $request->userName;
            $device->full_name = $request->fullName;
            $device->description = $request->description;
            $device->is_deleted = $request->isDeleted;
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
        $data = MUser::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = MUser::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request)
    {
        $userId = Auth()->user()->id;
        try {
            $device = MUser::find($request->Id);
            $device->ulb_id = $request->ulbId;
            $device->citizen_id = $request->citizenId;
            $device->employee_id = $request->employeeId;
            $device->vendor_id = $request->vendorId;
            $device->agency_id = $request->agencyId;
            $device->is_admin = $request->isAdmin;
            $device->is_psudo = $request->isPsudo;
            $device->email = $request->email;
            $device->user_name = $request->userName;
            $device->full_name = $request->fullName;
            $device->description = $request->description;
            $device->is_deleted = $request->isDeleted;
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
        $data = MUser::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
