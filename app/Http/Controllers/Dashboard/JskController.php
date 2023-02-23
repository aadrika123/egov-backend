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

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */

class JskController extends Controller
{
    use Workflow;
    /**
     * | Property Dashboard Details
     */
    public function propDtl(Request $request)
    {
        try {
            $userId = authUser()->id;
            $userType = authUser()->user_type;
            $ulbId = authUser()->ulb_id;
            $rUserType = array('TC', 'TL', 'JSK');
            $applicationType = $request->applicationType;
            $propActiveSaf =  new PropActiveSaf();
            $propTransaction =  new PropTransaction();
            $propConcession  = new PropActiveConcession();
            $propHarvesting = new PropActiveHarvesting();
            $propObjection = new PropActiveObjection();
            $propDeactivation = new PropActiveDeactivationRequest();
            $mWorkflowTrack = new WorkflowTrack();

            $currentRole =  $this->getRoleByUserUlbId($ulbId, $userId);


            // if (in_array($userType, $rUserType)) {

            $data['recentApplications']  = $propActiveSaf->recentApplication($userId);

            switch ($applicationType) {
                    //Concession
                case ('Concession'):
                    $data['recentApplications']  = $propConcession->recentApplication($userId);
                    break;

                    //Harvesting
                case ('Harvesting'):
                    $data['recentApplications']  = $propHarvesting->recentApplication($userId);
                    break;

                    //Objection
                case ('Objection'):
                    $data['recentApplications']  = $propObjection->recentApplication($userId);
                    break;

                    //Deactivation
                case ('Deactivation'):
                    $data['recentApplications']  = $propDeactivation->recentApplication($userId);
                    break;
            }

            $data['recentPayments']  = $propTransaction->recentPayment($userId);
            // }

            if ($userType == 'BO') {

                // $data['Total Received'] =  $propActiveSaf->totalReceivedApplication($currentRole->id);
                // return $mWorkflowTrack->totalForwadedApplication($currentRole->id);
            }

            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    public function jskPropDashboard(Request $request)
    {
        try {
            $userId = authUser()->id;
            $userType = authUser()->user_type;
            $ulbId = authUser()->ulb_id;
            $rUserType = array('TC', 'TL', 'JSK');
            $role = array('Back Office', 'Section Incharge', 'Dealing Assistant');
            $propertyWorflows = array(3, 4, 5, 106, 169, 182, 183, 212, 197);
            $date = Carbon::now();
            $mpropActiveSaf =  new PropActiveSaf();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mTempTransaction = new TempTransaction();
            $mWorkflowTrack = new WorkflowTrack();
            $currentRole =  $this->getRoleByUserUlbId($ulbId, $userId);


            if (in_array($userType, $rUserType)) {
                $a = $mpropActiveSaf->todayAppliedApplications($userId);
                $b = $mPropActiveObjection->todayAppliedApplications($userId);
                $c = $mPropActiveConcession->todayAppliedApplications($userId);
                $d = $mPropActiveHarvesting->todayAppliedApplications($userId);
                $e = $mTempTransaction->transactionList($date, $userId, $ulbId);
                $total = collect($e)->sum('amount');
                $cash = collect($e)->where('payment_mode', 'CASH')->sum('amount');
                $cheque = collect($e)->where('payment_mode', 'CHEQUE')->sum('amount');
                $dd = collect($e)->where('payment_mode', 'DD')->sum('amount');
                $online = collect($e)->where('payment_mode', 'Online')->sum('amount');

                $data['totalAppliedApplication'] = $a->union($b)->union($c)->union($d)->get()->count();
                $data['totalCollection'] = $total;
                $data['totalCash'] = $cash;
                $data['totalCheque'] = $cheque;
                $data['totalDD'] = $dd;
                $data['totalOnline'] = $online;
            }


            if (in_array($currentRole->role_name, $role)) {
                $safReceivedApp =  $mpropActiveSaf->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $objectionReceivedApp =  $mPropActiveObjection->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $concessionReceivedApp =  $mPropActiveConcession->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $harvestingReceivedApp =  $mPropActiveHarvesting->todayReceivedApplication($currentRole->id, $ulbId)->count();
                $data['totalReceivedApplication'] = $safReceivedApp + $objectionReceivedApp + $concessionReceivedApp + $harvestingReceivedApp;

                $data['totalForwadedApplication'] = $mWorkflowTrack->todayForwadedApplication($currentRole->id, $ulbId, $propertyWorflows);
            }


            if ($currentRole->role_name == 'Executive Officer') {
                $data['totalApprovedApplication'] =  $mWorkflowTrack->todayApprovedApplication($currentRole->id, $ulbId, $propertyWorflows)->count();
                $data['totalRejectedApplication'] = $mWorkflowTrack->todayRejectedApplication($currentRole->id, $ulbId, $propertyWorflows)->count();
            }

            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
