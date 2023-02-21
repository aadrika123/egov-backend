<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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
            $mpropActiveSaf =  new PropActiveSaf();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropActiveHarvesting = new PropActiveHarvesting();


            $a = $mpropActiveSaf->todayAppliedApplications($userId);
            $b = $mPropActiveObjection->todayAppliedApplications($userId);
            $c = $mPropActiveConcession->todayAppliedApplications($userId);
            $d = $mPropActiveHarvesting->todayAppliedApplications($userId);

            $data = $a->union($b)->union($c)->union($d)->get()->count();
            // return $data->count();



            return responseMsgs(true, "JSK Dashboard", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
