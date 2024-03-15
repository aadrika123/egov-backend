<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveDeactivationRequest;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropTransaction;
use App\Models\WorkflowTrack;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */

 
class JskController extends Controller
{
    use Workflow;
    /**
     * | Property Dashboard Details
     * /Written by prity pandey
     * 15-05-2024
     */

    public function propDashboardDtl(Request $request)
    {
        try {
            $user = authUser($request);
            $userId = $user->id;
            $userType = $user->user_type;
            $ulbId = $user->ulb_id;
            $applicationType = $request->applicationType;
            $propActiveSaf =  new PropActiveSaf();
            $propTransaction =  new PropTransaction();
            $propConcession  = new PropActiveConcession();
            $propHarvesting = new PropActiveHarvesting();
            $propObjection = new PropActiveObjection();
            $propDeactivation = new PropActiveDeactivationRequest();
            $mWfRoleUser = new WfRoleusermap();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mWfWardUser = new WfWardUser();
            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $data['recentApplications'] = $propActiveSaf->recentApplication($workflowIds, $roleIds, $ulbId);
            switch ($applicationType) {
                    //Concession
                case ('Concession'):
                    $data['recentApplications']  = $propConcession->recentApplication($workflowIds, $roleIds, $ulbId);
                    break;

                    //Harvesting
                case ('Harvesting'):
                    $data['recentApplications']  = $propHarvesting->recentApplication($workflowIds, $roleIds, $ulbId);
                    break;

                    //Objection
                case ('Objection'):
                    $data['recentApplications']  = $propObjection->recentApplication($workflowIds, $roleIds, $ulbId);
                    break;

                    //Deactivation
                case ('Deactivation'):
                    $data['recentApplications']  = $propDeactivation->recentApplication($workflowIds, $roleIds, $ulbId);
                    break;
            }

            if ($userType == 'JSK') {
                $data['recentApplications'] = $propActiveSaf->recentApplicationJsk($userId);

                switch ($applicationType) {
                        //Concession
                    case ('Concession'):
                        $data['recentApplications']  = $propConcession->recentApplicationJsk($userId);
                        break;

                        //Harvesting
                    case ('Harvesting'):
                        $data['recentApplications']  = $propHarvesting->recentApplicationJsk($userId);
                        break;

                        //Objection
                    case ('Objection'):
                        $data['recentApplications']  = $propObjection->recentApplicationJsk($userId);
                        break;

                        //Deactivation
                    case ('Deactivation'):
                        $data['recentApplications']  = $propDeactivation->recentApplicationJsk($userId);
                        break;
                }
                $data['recentPayments']  = $propTransaction->recentPayment($userId);
                return responseMsgs(true, "Recent Application", remove_null($data), "011901", "1.0", "", "POST", $request->deviceId ?? "");
            }
            return responseMsgs(true, "Recent Application", remove_null($data), "011901", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011901", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    public function propDashboard(Request $request)
    {
        try {
            $user     = authUser($request);
            $userId   = $user->id;
            $userType = $user->user_type;
            $ulbId    = $user->ulb_id;
            $rUserType = array('TC', 'TL', 'JSK');
            $role = array('BACK OFFICE', 'SECTION INCHARGE', 'DEALING ASSISTANT');
            $propertyWorflows = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13);
            $date = Carbon::now();
            $mpropActiveSaf =  new PropActiveSaf();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mPropActiveDeactivation = new PropActiveDeactivationRequest();
            $mTempTransaction = new TempTransaction();
            $mWorkflowTrack = new WorkflowTrack();
            $currentRole =  $this->getRoleByUserUlbId($ulbId, $userId);

            if (in_array($userType, $rUserType)) {
                $saf = $mpropActiveSaf->todayAppliedApplications($userId);
                $obj = $mPropActiveObjection->todayAppliedApplications($userId);
                $con = $mPropActiveConcession->todayAppliedApplications($userId);
                $har = $mPropActiveHarvesting->todayAppliedApplications($userId);
                $deactv = $mPropActiveDeactivation->todayAppliedApplications($userId);
                $tran = $mTempTransaction->transactionList($date, $userId, $ulbId);
                $total = collect($tran)->sum('amount');
                $cash = collect($tran)->where('payment_mode', 'CASH')->sum('amount');
                $cheque = collect($tran)->where('payment_mode', 'CHEQUE')->sum('amount');
                $dd = collect($tran)->where('payment_mode', 'DD')->sum('amount');
                $online = collect($tran)->where('payment_mode', 'Online')->sum('amount');

                $data['totalCollection'] = $total;
                $data['totalCash'] = $cash;
                $data['totalCheque'] = $cheque;
                $data['totalDD'] = $dd;
                $data['totalOnline'] = $online;
                $data['saf'] = $saf->count();
                $data['objection'] = $obj->count();
                $data['concession'] = $con->count();
                $data['harvesting'] = $har->count();
                $data['deactivation'] = $deactv->count();
                $data['totalApplication'] = $data['saf'] + $data['objection'] + $data['concession'] + $data['harvesting'] + $data['deactivation'];
            }

            if (in_array($currentRole->role_name, $role)) {
                $safReceivedApp =  $mpropActiveSaf->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $objectionReceivedApp =  $mPropActiveObjection->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $concessionReceivedApp =  $mPropActiveConcession->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $harvestingReceivedApp =  $mPropActiveHarvesting->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $deactivationReceivedApp =  $mPropActiveDeactivation->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $data['totalApplication'] = $safReceivedApp + $objectionReceivedApp + $concessionReceivedApp + $harvestingReceivedApp + $deactivationReceivedApp;
                $data['saf'] = $safReceivedApp;
                $data['objection'] = $objectionReceivedApp;
                $data['concession'] = $concessionReceivedApp;
                $data['harvesting'] = $harvestingReceivedApp;
                $data['deactivation'] = $deactivationReceivedApp;

                $data['totalForwadedApplication'] = $mWorkflowTrack->todayForwadedApplication($currentRole->id, $ulbId, $propertyWorflows)->count();
            }

            if ($currentRole->role_name == 'EXECUTIVE OFFICER') {
                $safReceivedApp =  $mpropActiveSaf->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $objectionReceivedApp =  $mPropActiveObjection->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $concessionReceivedApp =  $mPropActiveConcession->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $harvestingReceivedApp =  $mPropActiveHarvesting->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $deactivationReceivedApp =  $mPropActiveDeactivation->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $data['saf'] = $safReceivedApp;
                $data['objection'] = $objectionReceivedApp;
                $data['concession'] = $concessionReceivedApp;
                $data['harvesting'] = $harvestingReceivedApp;
                $data['deactivation'] = $deactivationReceivedApp;
                $data['totalApplication'] = $safReceivedApp + $objectionReceivedApp + $concessionReceivedApp + $harvestingReceivedApp + $deactivationReceivedApp;
                $data['totalApprovedApplication'] =  $mWorkflowTrack->todayApprovedApplication($currentRole->id, $ulbId, $propertyWorflows)->count();
                $data['totalRejectedApplication'] = $mWorkflowTrack->todayRejectedApplication($currentRole->id, $ulbId, $propertyWorflows)->count();
            }

            return responseMsgs(true, "JSK Dashboard", remove_null($data), "011902", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011902", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
