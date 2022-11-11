<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\WorkflowMaster\Interface\iWorkflowMappingRepository;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowMappingController
 * -------------------------------------------------------------------------------------------------
 * Created On-17-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class WorkflowMappingRepository implements iWorkflowMappingRepository
{
    //Mapping
    //getting data of user & ulb  by selecting  id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserById(Request $request)
    {
        $users = WfWardUser::where('wf_ward_users.id', $request->id)
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
        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('wf_roles.role_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        $workkFlow = UlbWardMaster::where('ulb_id', $request->ulbId)
            ->get('ward_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
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
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    // table = 6 & 7
    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $workkFlow = WfRoleusermap::where('wf_role_id', $request->roleId)
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
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get(['role_name', 'wf_roles.id']);
        return responseMsg(true, "Data Retrived", $users);
    }

    //get user by workflowId
    public function getUserByWorkflow(Request $request)
    {
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.*');
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
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    // get users in a role
    public function getUserByRoleId(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //get wards by role
    public function getWardByRole(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'users.id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
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
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
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
}
