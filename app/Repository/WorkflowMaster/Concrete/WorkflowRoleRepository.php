<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRole;
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
            $role = new WfRole;
            $role->role_name = $request->roleName;
            $role->created_by = $createdBy;
            $role->stamp_date_time = Carbon::now();
            $role->created_at = Carbon::now();
            $role->save();
            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * GetAll data
     */
    public function getAllRoles()
    {
        $data = WfRole::where('is_suspended', false)
            ->orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function deleteRole($request)
    {
        $data = WfRole::find($request->id);
        $data->is_suspended = true;
        $data->save();
        return responseMsg(true, 'Successfully Deleted', "");
    }


    /**
     * Update data
     */
    public function editRole(Request $request)
    {
        $createdBy = Auth()->user()->id;
        //validating
        $validateUser = Validator::make(
            $request->all(),
            [
                'roleName' => 'required',
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
            $role = WfRole::find($request->id);
            $role->role_name = $request->roleName;
            $role->is_suspended = $request->isSuspended;
            $role->created_by = $createdBy;
            $role->updated_at = Carbon::now();
            $role->save();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * list view by IDs
     */

    public function getRole($request)
    {
        $data = WfRole::where('id', $request->id)
            ->where('is_suspended', false)
            ->get();
        if ($data) {
            return responseMsg(true, 'Succesfully Retrieved', $data);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
