<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;
use Illuminate\Http\Request;
use App\Models\WfWardUser;
use App\Models\UlbWardMaster;
use App\Models\UlbMaster;
use App\Models\User;
use App\Models\WfRoleusermap;
use App\Models\WfWorkflow;
use App\Models\WfWorkflowrolemap;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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



class EloquentWorkflowWardUserRepository implements iWorkflowWardUserRepository
{

    public function create(Request $request)
    {
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
                'message' => 'validation error',
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
        //validation 
        $validateUser = Validator::make(
            $request->all(),
            [
                'userId' => 'required',
                'wardId' => 'required',
                'isAdmin' => 'required',
                'status' => 'required',
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
            $device = WfWardUser::find($request->id);
            $device->user_id = $request->userId;
            $device->ward_id = $request->wardId;
            $device->is_admin = $request->isAdmin;
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
        $data = WfWardUser::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }

    //Mapping
    //getting data of user & ulb  by selecting  id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserByID($id)
    {
        $users = WfWardUser::where('wf_ward_users.id', $id)
            ->join('users', 'users.id', '=', 'wf_ward_users.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get(['users.*', 'ulb_ward_masters.*']);
        return response(["data" => $users/*true, "Data Fetched", $users*/]);
    }



    //get AltName by UlbId
    public function getAltNameByUlbId(Request $request)
    {
        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->get('alt_name');
        return response()->json(["data" => $workkFlow]);
    }

    // tables = wf_workflows + wf_masters
    // ulbId -> workflow name
    // workflows in a ulb
    public function getWorkflowNameByUlb(Request $request)
    {
        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            // ->join('wf_workflows', 'wf_workflows.wf_master_id', '=', 'wf_masters.id')
            ->get('wf_masters.workflow_name');
        return response()->json(["data" => $workkFlow]);
    }

    // tables = wf_workflows + wf_workflowrolemap + wf_roles
    // ulbId -> rolename
    // roles in a ulb 
    public function getRoleByUlb(Request $request)
    {
        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('wf_roles.role_name');
        return response()->json(["data" => $workkFlow]);
    }

    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        $workkFlow = UlbWardMaster::where('ulb_id', $request->ulbId)
            ->get('ward_name');
        return response()->json(["data" => $workkFlow]);
    }

    // get role by workflow id
    public function getRoleByWorkflowId(Request $request)
    {
        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            // ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return response()->json(["data" => $workkFlow]);
    }

    // table = 6 & 7
    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $workkFlow = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.user_name');
        return response()->json(["data" => $workkFlow]);
    }

    //============================================================================================
    //=============================       NEW MAPPING          ===================================
    //============================================================================================


    //role in a workflow
    public function getRoleByWorkflow(Request $request)
    {
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get(['role_name', 'wf_roles.id']);
        return response()->json(["data" => $users]);
    }

    //get user by workflowId
    public function getUserByWorkflow(Request $request)
    {
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.*');
        return response()->json(["data" => $users]);
    }

    //wards in a workflow
    public function getWardsInWorkflow(Request $request)
    {
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'users.id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get('ulb_ward_masters.ward_name');
        return response()->json(["data" => $users]);
    }

    ///////////////////
    //ulb in a workflow
    public function getUlbInWorkflow(Request $request)
    {
        $users = WfWorkflow::where('wf_master_id', $request->id)
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get();
        return response()->json(["data" => $users]);
    }


    // ================================================================================
    // ================================================================================

    //get wf by role id
    public function getWorkflowByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return response()->json(["data" => $users]);
    }

    // get users in a role
    public function getUserByRoleId(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return response()->json(["data" => $users]);
    }

    //get wards by role
    public function getWardByRole(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'users.id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
            ->get();
        return response()->json(["data" => $users]);
    }

    //get ulb by role
    public function getUlbByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get('ulb_masters.*');
        return response()->json(["data" => $users]);
    }

    //==================================================
    //==================================================
    //users in ulb
    public function getUserInUlb(Request $request) /////
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.wf_user_id')
            ->get();
        return response()->json(["data" => $users]);
    }

    //role in ulb
    public function getRoleInUlb(Request $request)
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('role_name');
        return response()->json(["data" => $users]);
    }


    //workflow in ulb
    public function getWorkflowInUlb(Request $request)
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflows', 'wf_workflows.wf_matser_id', '=', 'wf_masters.id')
            ->get('wf_masters.workflow_name');
        return response()->json(["data" => $users]);
    }
}
