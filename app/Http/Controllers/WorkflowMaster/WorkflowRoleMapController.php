<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\RainWaterHarvestingController;
use App\Models\User;
use App\Models\Workflows\WfRoleusermap;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\Property\Concrete\PropertyBifurcation;
use App\Repository\Property\Concrete\PropertyDeactivate;
use App\Repository\Property\Interfaces\iConcessionRepository;
use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\NewConnectionRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleUserMapRepository;
use Exception;
use App\Traits\Workflow\Workflow;

use function PHPUnit\Framework\isNull;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowRoleMapController extends Controller
{
    use Workflow;
    //create master
    private $saf_repository;
    private $concession;
    private $objection;
    public function __construct(iSafRepository $saf_repository, iConcessionRepository $concession, iObjectionRepository $objection)
    {
        $this->saf_repository = new ActiveSafController($saf_repository);
        $this->concession = new ConcessionController($concession);
        $this->objection = new ObjectionController($objection);
    }

    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'workflowId' => 'required',
                'wfRoleId' => 'required',
                // 'forwardRoleId' => 'required',
                // 'backwardRoleId' => 'required',
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
            $mWorkflowMap = new WorkflowMap();
            $data = $mWorkflowMap->getRoleByWorkflow($req);
            $a['members'] = collect($data)['original']['data'];

            //logged in user role
            // $role = new WorkflowMap();
            $role = $this->getRole($req);
            if ($role->isEmpty())
                throw new Exception("You are not authorised");
            $roleId  = collect($role)['wf_role_id'];

            //members permission
            $a['permissions'] = $this->permission($req, $roleId);

            // pseudo users
            $a['pseudoUsers'] = $this->pseudoUser();

            //inbox
            switch ($req->workflowId) {

                    //Water
                case (16):
                    $inbox = new NewConnectionRepository;
                    $ab = $inbox->waterInbox();
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;

                    //Concession
                case (106):
                    $inbox = $this->concession;
                    $ab = $inbox->inbox();
                    $a['inbox'] = collect($ab);
                    break;

                    //objection for clerical
                case (169):
                    $inbox = $this->objection;
                    $ab = $inbox->inbox();
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;

                    //rain water harvesting
                case (197):
                    $inbox = new RainWaterHarvestingController;
                    $ab = $inbox->harvestingInbox();
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;

                    //trade
                case (10):
                    $inbox = new Trade();
                    $ab = $inbox->inbox($req);
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;

                    //deactivation
                case (167):
                    $inbox = new PropertyDeactivate();
                    $ab = $inbox->inbox($req);
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;

                    //SAF
                case (3 || 4 || 5 || 182 || 381):
                    $inbox = $this->saf_repository;
                    $ab = $inbox->inbox();
                    collect($ab)['original']['data'];
                    $a['inbox'] = collect($ab)['original']['data'];
                    break;
            }

            return responseMsgs(true, "Workflow Information", remove_null($a));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // tabs permission
    public function permission($req, $roleId)
    {
        $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
            ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
            ->where('wf_workflowrolemaps.wf_role_id', $roleId)
            ->first();

        // switch ($req->workflowId) {
        //     case (3 || 4 || 5):
        //         $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
        //             // ->join('prop_active_safs', 'prop_active_safs.current_role', 'wf_workflowrolemaps.wf_role_id')
        //             ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
        //             ->where('wf_workflowrolemaps.wf_role_id', $roleId)
        //             // ->where('prop_active_safs.id', $req->applicationId)
        //             ->first();
        //         break;

        //         //concession
        //     case (106):
        //         $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
        //             ->join('prop_active_concessions', 'prop_active_concessions.current_role', 'wf_workflowrolemaps.wf_role_id')
        //             ->where('wf_workflowrolemaps.workflow_id', $req->workflowId)
        //             ->where('wf_workflowrolemaps.wf_role_id', $roleId)
        //             ->where('prop_active_concessions.id', $req->applicationId)
        //             ->get();
        //         break;
        // }

        $data = [
            'allow_full_list' => $permission->allow_full_list,
            'can_escalate' => $permission->can_escalate,
            'can_btc' => $permission->is_btc,
            'is_enabled' => $permission->is_enabled,
            'can_view_document' => $permission->can_view_document,
            'can_upload_document' => $permission->can_upload_document,
            'can_verify_document' => $permission->can_verify_document,
            'allow_free_communication' => $permission->allow_free_communication,
            'can_forward' => $permission->can_forward,
            'can_backward' => $permission->can_backward,
            'can_approve' => $permission->is_finisher,
            'can_reject' => $permission->is_finisher,
            'is_pseudo' => $permission->is_pseudo,
            'show_field_verification' => $permission->show_field_verification,
            'can_view_form' => $permission->can_view_form,
            'can_see_tc_verification' => $permission->can_see_tc_verification,
            'can_edit' => $permission->can_edit,
            'can_send_sms' => $permission->can_send_sms,
            'can_comment' => $permission->can_comment,
            'is_custom_enabled' => $permission->is_custom_enabled,
        ];

        return $data;
    }

    public function pseudoUser()
    {
        $ulbId = authUser()->ulb_id;

        $pseudo = User::select(
            'id',
            'user_name'
        )
            ->where('user_type', 'Pseudo')
            ->where('ulb_id', $ulbId)
            ->where('suspended', false)
            ->get();
        return $pseudo;
    }
}
