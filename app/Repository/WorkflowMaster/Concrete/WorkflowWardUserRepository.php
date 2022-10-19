<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Models\WfRole;
use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;
use Illuminate\Http\Request;
use App\Models\WfWardUser;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowWardUserController
 * -------------------------------------------------------------------------------------------------
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class WorkflowWardUserRepository implements iWorkflowWardUserRepository
{

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;

        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'userId' => 'required',
                'wardId' => 'required',
                'isAdmin' => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validateUser->errors()
            ], 401);
        }

        try {
            $checkExisting = WfWardUser::where('user_id', $request->userId)
                ->where('ward_id', $request->wardId)
                ->first();
            if ($checkExisting) {
                $checkExisting->user_id = $request->userId;
                $checkExisting->ward_id = $request->wardId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }
            // create
            $device = new WfWardUser;
            $device->user_id = $request->userId;
            $device->ward_id = $request->wardId;
            $device->is_admin = $request->isAdmin;
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
        $data = WfWardUser::where('is_suspended', false)
            ->orderByDesc('id')->get();
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
        $createdBy = Auth()->user()->id;

        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'userId' => 'required',
                'wardId' => 'required',
                'isAdmin' => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validateUser->errors()
            ], 401);
        }
        try {
            $device = WfWardUser::find($id);
            $device->user_id = $request->userId;
            $device->ward_id = $request->wardId;
            $device->is_admin = $request->isAdmin;
            $device->createdBy = $createdBy;
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
        $data = WfWardUser::where('id', $id)
            ->where('is_suspended', true)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }

    public function getRoleDetails(Request $request)
    {
        $query = "SELECT 
                    w.*,
                    forwardd.role_name AS forward_role_name,
                    backwardd.role_name AS backwardd_role_name
                    FROM wf_roles w
                    LEFT JOIN wf_roles forwardd ON forwardd.id=w.forward_role_id
                    LEFT JOIN wf_roles backwardd ON backwardd.id=w.backward_role_id
                WHERE w.id=$request->roleId";
        $roles = DB::select($query);
        return responseMsg(true, "Data Fetched", remove_null($roles[0]));
    }
}
