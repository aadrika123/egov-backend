<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */


class StateDashboardController extends Controller
{
    /**
     * | Ulb Wise Collection
     */
    public function ulbWiseCollection(Request $req)
    {
        $currentYear = Carbon::now()->year;
        $financialYearStart = $currentYear;
        if (Carbon::now()->month < 4) {
            $financialYearStart--;
        }
        $financialYear = $financialYearStart . '-' . ($financialYearStart + 1);

        $fromDate = '01-04-' . $financialYearStart;
        $toDate   = '31-03-' . $financialYearStart + 1;

        $ulbIds = [1, 2];
        foreach ($ulbIds as $ulbId) {

            $sql = "WITH 
                    transaction AS 
                    (
                        SELECT SUM(amount) AS total FROM prop_transactions
                        WHERE ulb_id = $ulbId
                        AND verify_status = 1
                        AND tran_date BETWEEN '$fromDate' AND '$toDate'
                    union
                        (
                        SELECT SUM(paid_amount) AS total FROM trade_transactions
                        WHERE ulb_id = $ulbId
                        AND is_verified = true
                        AND tran_date BETWEEN '$fromDate' AND '$toDate'
                        )
                    union
                        (
                        SELECT SUM(amount) AS total FROM water_trans
                        WHERE ulb_id = $ulbId
                        AND verify_status = 1
                        AND tran_date BETWEEN '$fromDate' AND '$toDate'
                        )
                    )select * from  transaction";
            $data = DB::select($sql);
        }
        return $data;

        return $total =  collect($data)->sum('total');
    }
}
