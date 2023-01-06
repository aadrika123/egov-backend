<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workflows\WfRoleusermap;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleUserMapRepository;
use Exception;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowRoleMapController extends Controller
{
    //create master
    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'workflowId' => 'required',
                'wfRoleId' => 'required',
                'forwardRoleId' => 'required',
                'backwardRoleId' => 'required',
            ]);

            $create = new WfWorkflowrolemap();
            $create->addRoleMap($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update master
    public function updateRoleMap(Request $req)
    {
        try {
            $update = new WfWorkflowrolemap();
            $list  = $update->updateRoleMap($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //master list by id
    public function roleMapbyId(Request $req)
    {
        try {

            $listById = new WfWorkflowrolemap();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "Role Map List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all master list
    public function getAllRoleMap()
    {
        try {

            $list = new WfWorkflowrolemap();
            $masters = $list->roleMaps();

            return responseMsg(true, "All Role Map List", $masters);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete master
    public function deleteRoleMap(Request $req)
    {
        try {
            $delete = new WfWorkflowrolemap();
            $delete->deleteRoleMap($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //Workflow Info
    public function workflowInfo(Request $req)
    {
        try {
            //workflow members
            $data = new WorkflowMap();
            $data = $data->getRoleByWorkflow($req);
            $a['members'] = collect($data)['original']['data'];

            // $b = $a['member'] = collect($data)['original']['data'];
            // $listRoles = collect($b)->map(function ($value) {
            //     return $value->role_id;
            // });

            //members permission
            $a['permissions'] = $this->permission($req);

            // pseudo users
            $a['pseudoUsers'] = $this->pseudoUser();

            return responseMsgs(true, "Workflow Information", remove_null($a));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // tabs permission
    public function permission($req)
    {
        $moduleId = $req->moduleId;

        switch ($moduleId) {
            case (1):
                switch ($req->workflowId) {
                    case (3 || 4 || 5):
                        $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
                            ->join('prop_active_safs', 'prop_active_safs.current_role', 'wf_workflowrolemaps.wf_role_id')
                            // ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
                            ->where('prop_active_safs.id', $req->applicationId)
                            ->first();
                        break;

                        //concession
                    case (106):
                        $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
                            ->join('prop_active_concessions', 'prop_active_concessions.current_role', 'wf_workflowrolemaps.wf_role_id')
                            // ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
                            ->where('prop_active_concessions.id', $req->applicationId)
                            ->get();
                        break;
                }
                break;

            case (2):
                $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
                    ->join('water_applications', 'water_applications.current_role', 'wf_workflowrolemaps.wf_role_id')
                    // ->join('water_applications', 'water_applications.workflow_id', 'wf_workflowrolemaps.workflow_id')
                    // ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
                    ->where('water_applications.id', $req->applicationId)
                    ->first();
                break;

            case (3):
                break;

            case (4):
                break;
        }

        $data = [
            'specialPower' => $permission->show_full_list,
            'buttonEscalate' => $permission->escalation,
            'buttonBTC' => $permission->is_btc,
            'tabWorkflowAction' => $permission->wf_action,
            'tabViewDocument' => $permission->view_doc,
            'tabUploadDocument' => $permission->upload_doc,
            'tabVerifyDocument' => $permission->verify_doc,
            'tabFreeCommunication' => $permission->communication_tab,
            'buttonForward' => $permission->forward_btn,
            'buttonBackward' => $permission->backward_btn,
            'buttonApprove' => $permission->is_finisher,
            'buttonReject' => $permission->is_finisher,
            'tabsAllowed' => $permission->tab_allowed,
            'isPseudo' => $permission->is_pseudo,
            'fieldVerification' => $permission->field_verification,
            'payment' => $permission->payment,

        ];

        return $data;
    }

    public function pseudoUser()
    {
        // $data = new WorkflowRoleUserMapRepository();
        // return  $data = $data->roleUser();
        $ulbId = authUser()->ulb_id;

        $pseudo = User::select('users.*')
            ->where('user_type', 'Pseudo')
            ->where('ulb_id', $ulbId)
            ->where('suspended', false)
            ->get();
        return $pseudo;
    }
}
