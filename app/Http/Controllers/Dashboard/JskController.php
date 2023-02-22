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


            if (in_array($userType, $rUserType)) {

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
            }

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
                $f = collect($e)->sum('amount');
                $g = collect($e)->where('payment_mode', 'CASH')->sum('amount');
                $h = collect($e)->where('payment_mode', 'CHEQUE')->sum('amount');
                $i = collect($e)->where('payment_mode', 'DD')->sum('amount');
                $j = collect($e)->where('payment_mode', 'Online')->sum('amount');

                $data['totalAppliedApplication'] = $a->union($b)->union($c)->union($d)->get()->count();
                $data['totalCollection'] = $f;
                $data['totalCash'] = $g;
                $data['totalCheque'] = $h;
                $data['totalDD'] = $i;
                $data['totalOnline'] = $j;
            }


            if ($userType == 'BO') {

                $data['totalReceivedApplication'] =  $mpropActiveSaf->todayReceivedApplication($currentRole->id)->count();
                return $mWorkflowTrack->todayForwadedApplication($currentRole->id, $ulbId);
            }


            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
