<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
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
        $collection = collect();

        $ulbIds = [1, 2, 3, 4, 5];
        $ulbName = ['Adityapur', 'Ranchi'];

        foreach ($ulbIds as $ulbId) {
            $data['ulbId'] = $ulbId;
            $data['collection'] = $this->collection($ulbId, $fromDate, $toDate);
            $collection->push($data);
        }
        return responseMsgs(true, "Ulb Wise Collection", remove_null($collection), "", '', '01', '314ms-451ms', 'Post', '');
    }

    public function collection($ulbId, $fromDate, $toDate)
    {
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
        return collect($data)->pluck('total')->sum();
    }

    /**
     * | Module wise count of online payment
     */
    public function onlinePaymentCount(Request $req)
    {
        $today = Carbon::now()->format('y-m-d');
        $data = PropTransaction::where('payment_mode', 'ONLINE')
            ->where('tran_date', $today)
            ->get();

        $data = TradeTransaction::where('payment_mode', 'Online')
            ->where('tran_date', $today)
            ->get();

        return $data = WaterTran::where('payment_mode', 'Online')
            ->where('tran_date', $today)
            ->get();

        return $data->count();
    }

    /**
     * | State Wise Collection Percentage
     */
    public function stateWiseCollectionPercentage(Request $req)
    {
    }
}
