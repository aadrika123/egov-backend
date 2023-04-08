<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest\RequestCollectionPercentage;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
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
    public function stateWiseCollectionPercentage(RequestCollectionPercentage $req)
    {
        try {
            $currentYear = Carbon::now()->format('Y');
            switch ($req->parameter) {
                case ("month"):
                    if (isset($req->year)) {
                        $returnData = $this->getDataByMonth($req);
                    } else {
                        $returnData = $this->getDataByCurrentMonth($req, $currentYear);
                    }
                    break;
                case ("year"):
                    if (isset($req->year)) {
                        $returnData = $this->getDataByYear($req);
                    } else {
                        $returnData = $this->getDataByCurrentYear($req, $currentYear);
                    }
                    break;
            }
            return responseMsgs(true, $req->parameter . "  state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Details by Year
     */
    public function getDataByYear($req)
    {
        $prop = PropTransaction::select('amount as propAmount')
            ->whereYear('tran_date', $req->year)
            ->where("status", 1);
        $water = WaterTran::select('amount as waterAmount')
            ->whereYear('tran_date', $req->year)
            ->where("status", 1);
        $trade = TradeTransaction::select('paid_amount as tradeAmount')
            ->whereYear('tran_date', $req->year)
            ->where("status", 1);

        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();
        $collectiveData['totalAount'] = round(collect($prop->get())->sum('propAmount') + collect($water->get())->sum('waterAmount') + collect($trade->get())->sum('tradeAmount'),2);
        $collectiveData['propPercent'] = round((collect($prop->get())->sum('propAmount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['waterPercent'] = round((collect($water->get())->sum('waterAmount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['tradePercent'] = round((collect($trade->get())->sum('tradeAmount') / $collectiveData['totalAount']) * 100,2);
        return $collectiveData;
    }

    /**
     * | state wise collection data as per month
     * | @param 
     */
    public function getDataByCurrentYear($currentYear)
    {
        $prop = PropTransaction::whereYear('tran_date', $currentYear)
            ->where("status", 1);
        $water = WaterTran::whereYear('tran_date', $currentYear)
            ->where("status", 1);
        $trade = TradeTransaction::whereYear('tran_date', $currentYear)
            ->where("status", 1);

        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();
        $collectiveData['totalAount'] = round(collect($prop->get())->sum('amount') + collect($water->get())->sum('amount') + collect($trade->get())->sum('paid_amount'),2);
        $collectiveData['propPercent'] = round((collect($prop->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['waterPercent'] = round((collect($water->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['tradePercent'] = round((collect($trade->get())->sum('paid_amount') / $collectiveData['totalAount']) * 100,2);
        return $collectiveData;
    }

    /**
     * | Get the data of the online payment 
     */
    public function getDataByMonth($req)
    {
        $prop = PropTransaction::whereYear('tran_date', $req->year)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);
        $water = WaterTran::whereYear('tran_date', $req->year)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);
        $trade = TradeTransaction::whereYear('tran_date', $req->year)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);

        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();
        $collectiveData['totalAount'] = round(collect($prop->get())->sum('amount') + collect($water->get())->sum('amount') + collect($trade->get())->sum('paid_amount'),2);
        $collectiveData['propPercent'] = round((collect($prop->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['waterPercent'] = round((collect($water->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['tradePercent'] = round((collect($trade->get())->sum('paid_amount') / $collectiveData['totalAount']) * 100,2);
        return $collectiveData;
    }

    /**
     * | get data by month and year 
     */
    public function getDataByCurrentMonth($req, $currentYear)
    {
        $prop = PropTransaction::whereYear('tran_date', $currentYear)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);
        $water = WaterTran::whereYear('tran_date', $currentYear)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);
        $trade = TradeTransaction::whereYear('tran_date', $currentYear)
            ->whereMonth('date_column', $req->month)
            ->where("status", 1);

        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();
        $collectiveData['totalAount'] = round(collect($prop->get())->sum('amount') + collect($water->get())->sum('amount') + collect($trade->get())->sum('paid_amount'),2);
        $collectiveData['propPercent'] = round((collect($prop->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['waterPercent'] = round((collect($water->get())->sum('amount') / $collectiveData['totalAount']) * 100,2);
        $collectiveData['tradePercent'] = round((collect($trade->get())->sum('paid_amount') / $collectiveData['totalAount']) * 100,2);
        return $collectiveData;
    }



    /**
     * | Ulb wise Data
     */
    public function ulbWiseData(Request $req)
    {

        $ulbIds = [1, 2, 3, 4, 5];
        $collection = collect();
        $data = collect();

        foreach ($ulbIds as $ulbId) {

            $sql = "SELECT gbsaf,mix_commercial,pure_commercial,pure_residential,total_properties
                FROM
                    ( select count(*) as gbsaf from prop_properties 
                        where is_gb_saf = 'true' and  ulb_id = $ulbId and status =1 ) as gbsaf,
                    ( select count(*) as mix_commercial from prop_properties
                        where holding_type = 'MIX_COMMERCIAL' and  ulb_id = $ulbId and status =1) as mix_commercial,
                    (select count(*) as pure_commercial from prop_properties
                        where holding_type = 'PURE_COMMERCIAL'and  ulb_id = $ulbId and status =1) as pure_commercial,
                    (select count(*) as pure_residential from prop_properties
                        where holding_type = 'PURE_RESIDENTIAL'and  ulb_id = $ulbId and status =1) as pure_residential,
                    (select count(*) as total_properties from prop_properties where ulb_id = $ulbId and status =1) as total_properties";

            $a = DB::select($sql);


            $data['data'] = collect($a)->first();
            $data = json_decode(json_encode($data), true);
            $data['data']['ulb'] = $ulbId;

            // $data->push(['ulb' => $ulbId]);
            // $data['ulb'] = $ulbId;
            // $data['data']->merge($data['ulb']);
            $collection->push($data);
        }
        // $collection;
        return  $data = (array_values(objtoarray($collection)));
    }
}
