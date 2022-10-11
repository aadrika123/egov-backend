<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\MUser;
use Exception;

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
        try {
            // create
            $device = new MUser;
            $device->ulb_id = $request->UlbId;
            $device->citizen_id = $request->CitizenId;
            $device->employee_id = $request->EmployeeId;
            $device->vendor_id = $request->VendorId;
            $device->agency_id = $request->AgencyId;
            $device->is_admin = $request->IsAdmin;
            $device->is_psudo = $request->IsPsudo;
            $device->email = $request->Email;
            $device->user_name = $request->UserName;
            $device->full_name = $request->FullName;
            $device->description = $request->Description;
            $device->is_deleted = $request->IsDeleted;
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
    public function update(Request $request, $id)
    {
        try {
            $device = MUser::find($request->Id);
            $device->ulb_id = $request->UlbId;
            $device->citizen_id = $request->CitizenId;
            $device->employee_id = $request->EmployeeId;
            $device->vendor_id = $request->VendorId;
            $device->agency_id = $request->AgencyId;
            $device->is_admin = $request->IsAdmin;
            $device->is_psudo = $request->IsPsudo;
            $device->email = $request->Email;
            $device->user_name = $request->UserName;
            $device->full_name = $request->FullName;
            $device->description = $request->Description;
            $device->is_deleted = $request->IsDeleted;
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
        $data = MUser::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
