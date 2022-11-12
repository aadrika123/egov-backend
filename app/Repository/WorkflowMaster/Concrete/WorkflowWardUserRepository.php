<?php

namespace App\Repository\WorkflowMaster\Concrete;


use App\Repository\WorkflowMaster\Interface\iWorkflowWardUserRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\UlbWardMaster;
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
        $data = WfWardUser::where('id', $id)
            ->get();
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }


    //===========================================================================================
    //===========================     WORKFLOW MAPPING       ====================================
    //===========================================================================================

    //get role details by 
    // public function getRoleDetails(Request $request)
    // {
    //     $query = "SELECT 
    //                 w.*,
    //                 forwardd.role_name AS forward_role_name,
    //                 backwardd.role_name AS backwardd_role_name
    //                 FROM wf_roles w
    //                 LEFT JOIN wf_roles forwardd ON forwardd.id=w.forward_role_id
    //                 LEFT JOIN wf_roles backwardd ON backwardd.id=w.backward_role_id
    //             WHERE w.id=$request->roleId";
    //     $roles = DB::select($query);
    //     return responseMsg(true, "Data Fetched", remove_null($roles[0]));
    // }

    //duplicate of getroledetails
    public function getRoleDetails(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|int',
            'wfRoleId' => 'required|int'

        ]);
        $roleDetails = DB::table('wf_workflowrolemaps')
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->select('wf_workflowrolemaps.*', 'r.role_name as forward_role_name', 'rr.role_name as backward_role_name')
            ->where('workflow_id', $request->workflowId)
            ->where('wf_role_id', $request->wfRoleId)
            ->first();
        return responseMsg(true, "Data Retrived", remove_null($roleDetails));
    }


    //getting data of user & ulb  by selecting  id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserById(Request $request)
    {
        $request->validate([
            'wardUserId' => 'required|int'
        ]);
        $users = WfWardUser::where('wf_ward_users.id', $request->wardUserId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_ward_users.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get(['users.*', 'ulb_ward_masters.*']);
        return responseMsg(true, "Data Retrived", $users);
    }


    // tables = wf_workflows + wf_masters
    // ulbId -> workflow name
    // workflows in a ulb
    public function getWorkflowNameByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'required|int'
        ]);

        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            // ->join('wf_workflows', 'wf_workflows.wf_master_id', '=', 'wf_masters.id')
            ->get('wf_masters.workflow_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    // tables = wf_workflows + wf_workflowrolemap + wf_roles
    // ulbId -> rolename
    // roles in a ulb 
    public function getRoleByUlb(Request $request)
    {
        //validating

        $request->validate([
            'ulbId' => 'required|int'
        ]);
        try {
            $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)

                ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
                ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
                ->get('wf_roles.role_name');
            return responseMsg(true, "Data Retrived", $workkFlow);
        } catch (Exception $e) {
            return $e;
        }
    }

    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'required|int'
        ]);

        $workkFlow = UlbWardMaster::where('ulb_id', $request->ulbId)
            ->get('ward_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    // get role by workflow id
    // provide ulb id
    public function getRoleByWorkflowId(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'required|int'
        ]);

        $roles = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->select('wf_roles.*')
            // ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", remove_null($roles));
    }

    // table = 6 & 7
    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $workkFlow = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.user_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    //============================================================================================
    //=============================       NEW MAPPING          ===================================
    //============================================================================================


    //role in a workflow
    public function getRoleByWorkflow(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->select('wf_roles.id as role_id', 'wf_roles.role_name')
            ->get();
        // $roles=DB::table('wf_workflowrolemaps')
        //             ->where('workflow_id',$request->workflowId)
        return responseMsg(true, "Data Retrived", $users);
    }

    //get user by workflowId
    public function getUserByWorkflow(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
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
        return responseMsg(true, "Data Retrived", $users);
    }

    ///////////////////
    //ulb in a workflow
    public function getUlbInWorkflow(Request $request)
    {
        $users = WfWorkflow::where('wf_master_id', $request->id)
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }


    // ================================================================================
    // ================================================================================

    //get wf by role id
    public function getWorkflowByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->select('workflow_name')
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    // get users in a role
    // not working
    public function getUserByRoleId(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //get wards by role
    public function getWardByRole(Request $request)
    {
        try {
            $users = WfRoleusermap::where('wf_role_id', $request->roleId)
                ->select('ulb_masters.*')
                ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
                ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
                ->get();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data Available", "");
        } catch (Exception $e) {
            return $e;
        }
    }

    //get ulb by role
    public function getUlbByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get('ulb_masters.*');
        return responseMsg(true, "Data Retrived", $users);
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
            ->join('users', 'wf_roleusermaps.id', '=', 'wf_roleusermaps.wf_user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //role in ulb
    public function getRoleInUlb(Request $request)
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('role_name');
        return responseMsg(true, "Data Retrived", $users);
    }


    //workflow in ulb
    public function getWorkflowInUlb(Request $request)
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflows', 'wf_workflows.wf_matser_id', '=', 'wf_masters.id')
            ->get('wf_masters.workflow_name');
        return responseMsg(true, "Data Retrived", $users);
    }

    //get role by ulb & user id
    public function getRoleByUserUlbId(Request $request)
    {
        try {
            $users = WfRole::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('wf_roleusermaps.user_id', $request->userId)
                ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', 'wf_roles.id')
                ->join('wf_ward_users', 'wf_ward_users.user_id', 'wf_roleusermaps.user_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'wf_ward_users.ward_id')
                ->first();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data Available", "");
        } catch (Exception $e) {
            return $e;
        }
    }

    //get role by ward & ulb id
    // public function getRoleByWardUlbId(Request $request)
    // {
    //     $users = WfRole::select('wf_roles.*')
    //         ->where('ulb_ward_masters.ulb_id', $request->ulbId)
    //         ->where('ulb_ward_masters.id', $request->wardId)
    //         ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', 'wf_roles.id')
    //         ->join('users', 'users.id', 'wf_roleusermaps.user_id')
    //         ->join('wf_ward_users', 'wf_ward_users.user_id', 'users.id')
    //         ->join('ulb_ward_masters', 'ulb_ward_masters.ulb_id', 'wf_ward_users.ward_id')
    //         ->first();
    //     return responseMsg(true, "Data Retrived", $users);
    // }

    public function getRoleByWardUlbId(Request $request)
    {
        try {
            $users = UlbWardMaster::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('ulb_ward_masters.id', $request->wardId)
                ->join('wf_ward_users', 'wf_ward_users.ward_id', 'ulb_ward_masters.id')
                ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', 'wf_ward_users.user_id')
                ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
                ->first();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data available", "");
        } catch (Exception $e) {
            return $e;
        }
    }
}
