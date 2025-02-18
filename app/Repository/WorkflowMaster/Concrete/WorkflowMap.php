<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\UlbWardMaster;
use App\Models\User;
use Exception;


/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class WorkflowMap implements iWorkflowMapRepository
{
    //get role details by 
    public function getRoleDetails(Request $request)
    {
        // $ulbId = authUser($request)->ulb_id;
        // $request->validate([
        //     'workflowId' => 'required|int'

        // ]);
        $roleDetails = DB::table('wf_workflowrolemaps')
            ->select(
                'wf_workflowrolemaps.id',
                'wf_workflowrolemaps.workflow_id',
                'wf_workflowrolemaps.wf_role_id',
                'wf_workflowrolemaps.forward_role_id',
                'wf_workflowrolemaps.backward_role_id',
                'wf_workflowrolemaps.is_initiator',
                'wf_workflowrolemaps.is_finisher',
                'r.role_name as forward_role_name',
                'rr.role_name as backward_role_name'
            )
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->where('workflow_id', $request->workflowId)
            ->where('wf_role_id', $request->wfRoleId)
            ->first();
        return responseMsgs(true, "Data Retrived", remove_null($roleDetails), "025871", 1.0, "", "POST", 200);
    }


    //getting data of user & ulb  by selecting  ward user id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserById(Request $request)
    {
        $request->validate([
            'wardUserId' => 'required|int'
        ]);
        $mWfWardUser = WfWardUser::where('wf_ward_users.id', $request->wardUserId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_ward_users.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get(['users.*', 'ulb_ward_masters.*']);
        return responseMsgs(true, "Data Retrived", $mWfWardUser, "025872", 1.0, "", "POST", 200);
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

        $mWfWorkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->select('wf_masters.id', 'wf_masters.workflow_name')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkFlow, "025873", 1.0, "", "POST", 200);
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
            $mWfWorkFlow = WfWorkflow::where('ulb_id', $request->ulbId)

                ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
                ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
                ->get('wf_roles.role_name');
            return responseMsgs(true, "Data Retrived", $mWfWorkFlow, "025874", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025874", 1.0, "", "POST", 400);
        }
    }

    //workking
    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'nullable'
        ]);
        $ulbId = $request->ulbId ?? $request->authUser()->ulb_id;
        $wards = collect();
        $mUlbWardMaster = UlbWardMaster::select(
            'id',
            'ulb_id',
            'ward_name',
            'old_ward_name'
        )
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->orderby('id')
            ->get();

        $groupByWards = $mUlbWardMaster->groupBy('ward_name');
        foreach ($groupByWards as $ward) {
            $wards->push(collect($ward)->first());
        }
        $wards->sortBy('ward_name')->values();
        return responseMsgs(true, "Data Retrived", remove_null($wards), "025875", 1.0, "", "POST", 200);
    }

    // table = 6 & 7
    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $mWfRoleUserMap = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.user_name');
        return responseMsgs(true, "Data Retrived", $mWfRoleUserMap, "025876", 1.0, "", "POST", 200);
    }

    //============================================================================================
    //=============================       NEW MAPPING          ===================================
    //============================================================================================


    //role in a workflow
    public function getRoleByWorkflow(Request $request)
    {
        $ulbId = authUser()->ulb_id;
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $mWfWorkflowrolemap = WfWorkflowrolemap::select('wf_roles.id as role_id', 'wf_roles.role_name')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_workflows', 'wf_workflows.id', 'wf_workflowrolemaps.workflow_id')
            ->where('wf_workflows.ulb_id', $ulbId)
            ->where('workflow_id', $request->workflowId)
            ->where(function ($where) {
                $where->orWhereNotNull("wf_workflowrolemaps.forward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.backward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.serial_no");
            })
            ->orderBy('serial_no')
            ->get();

        return responseMsgs(true, "Data Retrived", $mWfWorkflowrolemap, "025877", 1.0, "", "POST", 200);
    }

    //get user by workflowId
    public function getUserByWorkflow(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $mWfWorkflowrolemap = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkflowrolemap, "025878", 1.0, "", "POST", 200);
    }

    //wards in a workflow
    public function getWardsInWorkflow(Request $request)
    {
        $mWfWorkflowrolemap = WfWorkflowrolemap::select('ulb_ward_masters.ward_name', 'ulb_ward_masters.id')
            ->where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkflowrolemap, "025879", 1.0, "", "POST", 200);
    }


    //ulb in a workflow
    public function getUlbInWorkflow(Request $request)
    {
        $mWfWorkFlow = WfWorkflow::where('wf_master_id', $request->id)
            ->select('ulb_masters.*')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkFlow, "025880", 1.0, "", "POST", 200);
    }



    //get wf by role id
    public function getWorkflowByRole(Request $request)
    {
        $mWfWorkflowrolemap = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->select('workflow_name')
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkflowrolemap, "025881", 1.0, "", "POST", 200);
    }

    // get users in a role
    public function getUserByRoleId(Request $request)
    {
        $mWfRoleUserMap = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfRoleUserMap, "025882", 1.0, "", "POST", 200);
    }

    //get wards by role
    public function getWardByRole(Request $request)
    {
        try {
            $mWfRoleUserMap = WfRoleusermap::where('wf_role_id', $request->roleId)
                ->select('ulb_masters.*')
                ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
                ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
                ->get();
            if ($mWfRoleUserMap) {
                return responseMsg(true, "Data Retrived", $mWfRoleUserMap);
            }
            return responseMsgs(false, "No Data Available", "", "025883", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025883", 1.0, "", "POST", 400);
        }
    }

    //get ulb by role
    public function getUlbByRole(Request $request)
    {
        $mWfWorkflowrolemap = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get('ulb_masters.*');
        return responseMsgs(true, "Data Retrived", $mWfWorkflowrolemap, "025884", 1.0, "", "POST", 200);
    }


    //users in a ulb
    public function getUserInUlb(Request $request) //
    {
        $mUsers = User::select('users.*')
            ->where('users.ulb_id', $request->ulbId)
            ->get();
        return responseMsgs(true, "Data Retrived", $mUsers, "025885", 1.0, "", "POST", 200);
    }

    //role in ulb
    public function getRoleInUlb(Request $request)
    {
        $mWfWorkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('role_name');
        return responseMsgs(true, "Data Retrived", $mWfWorkFlow, "025886", 1.0, "", "POST", 200);
    }

    // working
    // workflow in ulb
    public function getWorkflowInUlb(Request $request)
    {
        $mWfWorkFlow = WfWorkflow::select('wf_masters.workflow_name', 'wf_workflows.id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->where('wf_workflows.ulb_id', $request->ulbId)
            ->where('wf_masters.is_suspended',  false)
            ->where('wf_workflows.is_suspended',  false)
            ->get();
        return responseMsgs(true, "Data Retrived", $mWfWorkFlow, "025887", 1.0, "", "POST", 200);
    }

    //get role by ulb & user id
    public function getRoleByUserUlbId(Request $request)
    {
        try {
            $mWfRole = WfRole::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('wf_roleusermaps.user_id', $request->userId)
                ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', 'wf_roles.id')
                ->join('wf_ward_users', 'wf_ward_users.user_id', 'wf_roleusermaps.user_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'wf_ward_users.ward_id')
                ->first();
            if ($mWfRole) {
                return responseMsgs(true, "Data Retrived", $mWfRole, "025888", 1.0, "", "POST", 200);
            }
            return responseMsgs(false, "No Data Available", "", "025888", 1.0, "", "POST");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025888", 1.0, "", "POST", 400);
        }
    }

    //get role by ward & ulb id
    public function getRoleByWardUlbId(Request $request)
    {

        try {
            $mUlbWardMaster = UlbWardMaster::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('ulb_ward_masters.id', $request->wardId)
                ->join('wf_ward_users', 'wf_ward_users.ward_id', 'ulb_ward_masters.id')
                ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', 'wf_ward_users.user_id')
                ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
                ->first();
            if ($mUlbWardMaster) {
                return responseMsgs(true, "Data Retrived", $mUlbWardMaster, "025889", 1.0, "", "POST", 200);
            }
            return responseMsgs(false, "No Data available", "", "025889", 1.0, "", "POST");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025889", 1.0, "", "POST", 400);
        }
    }

    //working
    //get workflow by ulb and master id
    public function getWorkflow(Request $request)
    {
        $request->validate([
            "ulbId" => "required|numeric",
            "workflowMstrId" => "required|numeric",

        ]);
        try {
            $mWfWorkflow = WfWorkflow::select('wf_workflows.*')
                ->where('ulb_id', $request->ulbId)
                ->where('wf_master_id', $request->workflowMstrId)
                ->where('is_suspended', false)
                ->first();
            if ($mWfWorkflow) {
                return responseMsgs(true, "Data Retrived", remove_null($mWfWorkflow), "025890", 1.0, "", "POST", 200);
            }
            return responseMsgs(false, "No Data available", "", "025890", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025890", 1.0, "", "POST", 400);
        }
    }
}
