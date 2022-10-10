<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\WfWardUser;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowWardUserController
 * -------------------------------------------------------------------------------------------------
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class EloquentWorkflowWardUserRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {

        try {
            $checkExisting = WfWardUser::where('user_id', $request->UserId)
                ->where('ward_id', $request->WardId)
                ->first();
            if ($checkExisting) {
                $checkExisting->user_id = $request->UserId;
                $checkExisting->ward_id = $request->WardId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfWardUser;
            $device->user_id = $request->UserId;
            $device->ward_id = $request->WardId;
            $device->is_admin = $request->IsAdmin;
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
        $data = WfWardUser::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfWardUser::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = WfWardUser::find($request->Id);
            $device = new WfWardUser;
            $device->user_id = $request->UserId;
            $device->ward_id = $request->WardId;
            $device->is_admin = $request->IsAdmin;
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
        $data = WfWardUser::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }

    //Mapping
    // getting data of user  by selecting  ward user id

    // public function getUserByID($id)
    // {
    //     $users = WfWardUser::where('user_id', $id)
    //         ->select('ulb_id')
    //         ->join('m_users', 'm_users.id', '=', 'wf_ward_users.user_id')
    //         ->get('wf_ward_users.ward_id');
    //     return response(["data" => $users/*true, "Data Fetched", $users*/]);
    // }

    // public function getUlbByID($id)
    // {
    //     $users = WfWardUser::where('ward_id', $id)
    //         ->select('ulb_id as ward_id', 'ward_name')
    //         ->join('m_ulb_wards', 'm_ulb_wards.id', '=', 'wf_ward_users.ward_id')
    //         ->get();
    //     return response([true, "Data Fetched", $users]);
    // }
}
