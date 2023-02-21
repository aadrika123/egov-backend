<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropTransaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */

class JskController extends Controller
{
    /**
     * | Property Dashboard Details
     */
    public function propDtl(Request $request)
    {
        try {
            $userId = authUser()->id;
            $propActiveSaf =  new PropActiveSaf();
            $propTransaction =  new PropTransaction();

            $data['recentApplications']  = $propActiveSaf->recentApplication($userId);

            $data['recentPayments']  = $propTransaction->recentPayment($userId);

            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    public function jskPropDashboard(Request $request)
    {
        try {
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $date = Carbon::now();
            $mpropActiveSaf =  new PropActiveSaf();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mTempTransaction = new TempTransaction();


            $a = $mpropActiveSaf->todayAppliedApplications($userId);
            $b = $mPropActiveObjection->todayAppliedApplications($userId);
            $c = $mPropActiveConcession->todayAppliedApplications($userId);
            $d = $mPropActiveHarvesting->todayAppliedApplications($userId);
            $e = $mTempTransaction->transactionList($date, $userId, $ulbId);
            $f =  collect($e)->sum('amount');
            $g = collect($e)->where('payment_mode', 'CASH')->sum('amount');
            $h = collect($e)->where('payment_mode', 'CHEQUE')->sum('amount');
            $i = collect($e)->where('payment_mode', 'DD')->sum('amount');
            $j = collect($e)->where('payment_mode', 'Online')->sum('amount');

            $data['Total Applied Application'] = $a->union($b)->union($c)->union($d)->get()->count();
            $data['Total Collection'] = $f;
            $data['Total Cash'] = $g;
            $data['Total Cheque'] = $h;
            $data['Total DD'] = $i;
            $data['Total Online'] = $j;




            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
