<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest\RequestCollectionPercentage;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\ulbRevenueTargete;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterTran;
use App\Repository\Common\CommonFunction;
use App\Repository\Dashboard\IStateDashboard;
use App\Repository\Dashboard\StateDashboard;
use App\Traits\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use stdClass;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */


class StateDashboardController extends Controller
{
    use Auth;

    private $Repository;
    private $_common;

    /**
     * | Ulb Wise Collection
     */
    public function ulbWiseCollection(Request $req)
    {
        try {
            $ulbs = UlbMaster::all();
            $year = Carbon::now()->year;

            if (isset($req->fyear))
                $year = substr($req->fyear, 0, 4);

            $financialYearStart = $year;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }

            $fromDate = $financialYearStart . '-04-01';
            $toDate   = $financialYearStart + 1 . '-03-31';
            $collection = collect();

            $ulbIds = $ulbs->pluck('id');
            
            foreach ($ulbIds as $ulbId) {                
                $data['ulbId'] = $ulbId;
                $data['ulb'] = $ulbs->where('id', $ulbId)->firstOrFail()->ulb_name;
                // $data['collection'] = $this->collection($ulbId, $fromDate, $toDate);
                $dtl = $this->Allcollection($ulbId, $fromDate, $toDate);
                $data['collection'] = $dtl["total"];
                $data['dtl'] = $dtl["Dtl"];
                $collection->push($data);
            }
            $collection = $collection->sortBy('ulbId')->values();

            ###################################################################################################
            # to be removed after akola project
            // $ward = UlbWardMaster::all();
            // $ward = DB::table('akola_wards')->get();
            // $wardIds = $ward->pluck('id');

            // foreach ($wardIds as $wardId) {
            //     $data['ulbId'] = $wardId;
            //     $data['ulb'] = $ward->where('id', $wardId)->firstOrFail()->ward_name;
            //     $data['collection'] = $ward->where('id', $wardId)->firstOrFail()->old_ward_name;
            //     $collection->push($data);
            // }
            // $collection = $collection->sortBy('ulbId')->values();

            return responseMsgs(true, "Ulb Wise Collection", remove_null($collection), "", '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '01', responseTime(), 'Post', '');
        }
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

    public function Allcollection($ulbId, $fromDate, $toDate)
    {
        $propertyCollection = $this->PropertyCollection($ulbId,$fromDate,$toDate);
        $waterCollection = $this->waterCollection($ulbId, $fromDate, $toDate);
        $tradeCollection = $this->tradeCollection($ulbId, $fromDate, $toDate);
        $total = ($propertyCollection->total??0) + ($waterCollection->total??0) + ($tradeCollection->total??0) ;

        return collect(["total"=>$total,"Dtl"=>[$propertyCollection,$waterCollection,$tradeCollection]]);
    }

    public function PropertyCollection($ulbId, $fromDate, $toDate)
    {
        $data = PropTransaction::on("pgsql::read")->select(DB::raw("COALESCE(SUM(amount),0) as total , 'property' as module"))
                ->where("ulb_id",$ulbId)
                ->where("verify_status",1)
                ->whereIn("status",[1,2])
                ->whereBetween("tran_date",[$fromDate,$toDate])
                ->first();
        return $data;
    }

    public function waterCollection($ulbId, $fromDate, $toDate)
    {       
        $data = WaterTran::on("pgsql_water::read")->select(DB::raw("COALESCE(SUM(amount),0) as total , 'water' as module"))
                ->where("ulb_id",$ulbId)
                ->where("verify_status",1)
                ->whereIn("status",[1,2])
                ->whereBetween("tran_date",[$fromDate,$toDate])
                ->first();
        return $data;
    }

    public function tradeCollection($ulbId, $fromDate, $toDate)
    {        
        $data = TradeTransaction::readConnection()->select(DB::raw("COALESCE(SUM(paid_amount),0) as total, 'trade' as module"))
                ->where("ulb_id",$ulbId)
                ->where("is_verified",TRUE)
                ->whereIn("status",[1,2])
                ->whereBetween("tran_date",[$fromDate,$toDate])
                ->first();
        return $data;
    }

    /**
     * | Module wise count of online payment
     */
    public function onlinePaymentCount(Request $req)
    {
        $year = Carbon::now()->year;

        if (isset($req->fyear))
            $year = substr($req->fyear, 0, 4);

        $financialYearStart = $year;
        if (Carbon::now()->month < 4) {
            $financialYearStart--;
        }

        $fromDate =  $financialYearStart . '-04-01';
        $toDate   =  $financialYearStart + 1 . '-03-31';

        if ($req->financialYear) {
            $fy = explode('-', $req->financialYear);
            $strtYr = collect($fy)->first();
            $endYr = collect($fy)->last();
            $fromDate =  $strtYr . '-04-01';
            $toDate   =  $endYr . '-03-31';;
        }

        $propTran = PropTransaction::select('id')
            ->where('payment_mode', 'ONLINE')
            ->whereBetween('tran_date', [$fromDate, $toDate]);
        $tradeTran = TradeTransaction::select('id')
            ->whereIN(DB::raw('upper(payment_mode)'), ['ONLINE',"ONL"])
            ->whereBetween('tran_date', [$fromDate, $toDate]);

        $waterTran = WaterTran::select('id')
            ->where('payment_mode', 'Online')
            ->whereBetween('tran_date', [$fromDate, $toDate]);

        $totalCount['propCount'] = $propTran->count();
        $totalCount['tradeCount'] = $tradeTran->count();
        $totalCount['waterCount'] = $waterTran->count();
        $totalCount['totalCount'] =  $totalCount['propCount'] + $totalCount['tradeCount'] + $totalCount['waterCount'];

        return responseMsgs(true, "Online Payment Count", remove_null($totalCount), "", '', '01', responseTime(), $req->getMethod(), '');
    }

    /**
     * | State Wise Collection Percentage
     */
    public function stateWiseCollectionPercentage(RequestCollectionPercentage $req)
    {
        try {
            $currentYear = Carbon::now()->format('Y');
            if (isset($req->month)) {
                $month = str_pad($req->month, 2, '0', STR_PAD_LEFT);
                $firstDate = date("Y-m-01", strtotime("$currentYear-$month-01"));
                $lastDate = date("Y-m-t", strtotime("$currentYear-$month-01"));
                $fromDate = $firstDate;
                $toDate   = $lastDate;
                $returnData = $this->getDataByCurrentMonth($req, $fromDate, $toDate);
                return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            }
            if (isset($req->month) && $req->year) {
                $fy = explode('-', $req->year);
                $strtYr = collect($fy)->first();
                $month = str_pad($req->month, 2, '0', STR_PAD_LEFT);
                $firstDate = date("Y-m-01", strtotime("$req->year-$month-01"));
                $lastDate = date("Y-m-t", strtotime("$req->year-$month-01"));
                $fromDate = $firstDate;
                $toDate   = $lastDate;
                $returnData = $this->getDataByMonthYear($req, $fromDate, $toDate);
                return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            }
            if (isset($req->year)) {
                $fy = explode('-', $req->year);
                $strtYr = collect($fy)->first();
                $endYr = collect($fy)->last();
                $fromDate = $strtYr . '-04-01';
                $toDate   =  $endYr . '-03-31';
                return$returnData = $this->getDataByYear($fromDate, $toDate);
                return responseMsgs(true, "  state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            } else {
                $financialYearStart = $currentYear;
                if (Carbon::now()->month < 4) {
                    $financialYearStart--;
                }
                $fromDate =  $financialYearStart  . '-04-01';
                $toDate   =  $financialYearStart + 1 . '-03-31';
                $returnData = $this->getDataByCurrentYear($fromDate, $toDate);
            }
            return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Details by Year
     */
    public function getDataByYear($fromDate, $toDate)
    { 
        $prop = PropTransaction::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select(DB::raw('sum(paid_amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $prop = $prop->get();
        $water = $water->get();
        $trade = $trade->get();
        $collectiveData['propAmount'] = (collect($prop)->sum('amount'));
        $collectiveData['waterAmount'] = (collect($water)->sum('amount'));
        $collectiveData['tradeAmount'] = (collect($trade)->sum('amount'));

        $collectiveData['totalAount'] = round($collectiveData['propAmount'] + $collectiveData['waterAmount'] + $collectiveData['tradeAmount'], 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = collect($prop)->sum("count") + collect($water)->sum("count") + collect($trade)->sum("count");

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    
    }

    /**
     * | state wise collection data as per month
     * | @param 
     */
    public function getDataByCurrentYear($fromDate, $toDate)
    {
        $prop = PropTransaction::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select(DB::raw('sum(paid_amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $prop = $prop->get();
        $water = $water->get();
        $trade = $trade->get();
        $collectiveData['propAmount'] = (collect($prop)->sum('amount'));
        $collectiveData['waterAmount'] = (collect($water)->sum('amount'));
        $collectiveData['tradeAmount'] = (collect($trade)->sum('amount'));

        $collectiveData['totalAount'] = round($collectiveData['propAmount'] + $collectiveData['waterAmount'] + $collectiveData['tradeAmount'], 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = collect($prop)->sum("count") + collect($water)->sum("count") + collect($trade)->sum("count");

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }

    /**
     * | get data by month and current year 
     */
    public function getDataByCurrentMonth($req, $fromDate, $toDate)
    {
        $prop = PropTransaction::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select(DB::raw('sum(paid_amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $prop = $prop->get();
        $water = $water->get();
        $trade = $trade->get();
        $collectiveData['propAmount'] = (collect($prop)->sum('amount'));
        $collectiveData['waterAmount'] = (collect($water)->sum('amount'));
        $collectiveData['tradeAmount'] = (collect($trade)->sum('amount'));

        $collectiveData['totalAount'] = round($collectiveData['propAmount'] + $collectiveData['waterAmount'] + $collectiveData['tradeAmount'], 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = collect($prop)->sum("count") + collect($water)->sum("count") + collect($trade)->sum("count");

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }


    /**
     * | get data by month and year 
     */
    public function getDataByMonthYear($req, $fromDate, $toDate)
    {
        $prop = PropTransaction::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select(DB::raw('sum(amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select(DB::raw('sum(paid_amount) as amount,count(id) as count'))
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $prop = $prop->get();
        $water = $water->get();
        $trade = $trade->get();
        $collectiveData['propAmount'] = (collect($prop)->sum('amount'));
        $collectiveData['waterAmount'] = (collect($water)->sum('amount'));
        $collectiveData['tradeAmount'] = (collect($trade)->sum('amount'));

        $collectiveData['totalAount'] = round($collectiveData['propAmount'] + $collectiveData['waterAmount'] + $collectiveData['tradeAmount'], 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = collect($prop)->sum("count") + collect($water)->sum("count") + collect($trade)->sum("count");

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }


    /**
     * | Ulb wise Data
     */
    public function districtWiseData(Request $req)
    {
        $req->validate([
            'districtCode' => 'required|integer'
        ]);

        try {
            $districtCode = $req->districtCode;
            $mUlbWardMstrs = new UlbMaster();
            $collection = collect();
            $data = collect();

            // Derivative Assignments
            $ulbs = $mUlbWardMstrs->getUlbsByDistrictCode($districtCode);
            if ($ulbs->isEmpty())
                throw new Exception("Ulbs Not Available for this district");

            $ulbIds = $ulbs->pluck('id');
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

                $data = collect($a)->first();
                $data = json_decode(json_encode($data), true);
                $data['ulb'] = $ulbs->where('id', $ulbId)->firstOrFail()->ulb_name;

                $collection->push($data);
            }
            $data = (array_values(objtoarray($collection)));
            return responseMsgs(true, "District Wise Collection", remove_null($data));
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), remove_null($data));
        }
    }

    # dashboard repots
    public function stateDashboardDCB(Request $request, StateDashboard $Repository)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["ds11.1", 1.1, null, $request->getMethod(), null,]]);
        return $Repository->stateDashboardDCB($request);
    }

    public function ulbsTargets(Request $request)
    {
        if(!$request->metaData)
        {
            $request->merge(["metaData" => ["ds11.1", 1.1, null, $request->getMethod(), null,]]);
        }
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        $validation = Validator::make($request->all(),[
            "fiYear" => "nullable||regex:/^\d{4}-\d{4}$/",
            "wardId" => "nullable|digits_between:1,9223372036854775807",
            "userId" => "nullable|digits_between:1,9223372036854775807",
            "ulbId" => "nullable|digits_between:1,9223372036854775807",
        ]);
        if($validation->fails())
        {
            return responseMsgs(false, "given Data invalid", $validation->errors(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
        try{
            $toDaye = Carbon::now()->format("Y-m-d");
            $fiYear = getFY();
            $ulbId = null;
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear."-04-01" ;
            $uptoDate = $toYear."-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            $target = ulbRevenueTargete::select(DB::raw(
                        "ulb_revenue_targetes.ulb_id,ulb_masters.ulb_name,
                        SUM(ulb_revenue_targetes.amt_prop_trg) AS amt_prop_trg, 
                        SUM(ulb_revenue_targetes.amt_prop_arr_trg) AS amt_prop_arr_trg,
                        SUM(ulb_revenue_targetes.amt_prop_curr_trg ) AS amt_prop_curr_trg,	
                        SUM(ulb_revenue_targetes.amt_water_trg) AS amt_water_trg,
                        SUM(ulb_revenue_targetes.amt_water_arr_trg) AS amt_water_arr_trg,
                        SUM(ulb_revenue_targetes.amt_water_curr_trg) AS amt_water_curr_trg,	
                        SUM(ulb_revenue_targetes.amt_trade_trg) AS amt_trade_trg,
                        SUM(ulb_revenue_targetes.amt_trade_arr_trg) AS amt_trade_arr_trg,
                        SUM(ulb_revenue_targetes.amt_trade_curr_trg) AS amt_trade_curr_trg
                        "
                    ))
                    ->join("ulb_masters","ulb_masters.id","ulb_revenue_targetes.ulb_id")
                    ->where("ulb_revenue_targetes.status",1)
                    ->whereBetween("ulb_revenue_targetes.effected_from",[$fromDate,$uptoDate])
                    ->groupBy("ulb_revenue_targetes.ulb_id")
                    ->groupBy("ulb_masters.ulb_name");
            if($ulbId)
            {
                $target = $target->where("ulb_masters.id",$ulbId);
            }

            $target = $target->get();
            $propColl = propTransaction::select(DB::raw("SUM(prop_transactions.amount) AS amount,prop_transactions.ulb_id, ulb_masters.ulb_name"))
                        ->join("ulb_masters","ulb_masters.id","prop_transactions.ulb_id")
                        ->whereNotNull("prop_transactions.property_id")
                        ->whereIn("prop_transactions.status",[1,2])                        
                        ->whereIN("prop_transactions.ulb_id",$target->pluck("ulb_id")->unique())
                        ->groupBy("prop_transactions.ulb_id")
                        ->groupBy("ulb_masters.ulb_name");  
            $propTodayColl =  propTransaction::select(DB::raw("SUM(prop_transactions.amount) AS amount,prop_transactions.ulb_id, ulb_masters.ulb_name"))
                        ->join("ulb_masters","ulb_masters.id","prop_transactions.ulb_id")
                        ->whereNotNull("prop_transactions.property_id")
                        ->whereIn("prop_transactions.status",[1,2])                        
                        ->whereIN("prop_transactions.ulb_id",$target->pluck("ulb_id")->unique())
                        ->groupBy("prop_transactions.ulb_id")
                        ->groupBy("ulb_masters.ulb_name");                     
            $propTodayColl =$propTodayColl->where("prop_transactions.tran_date",$toDaye)->get();
            $propColl = $propColl->whereBetween("prop_transactions.tran_date",[$fromDate,$uptoDate])->get()->map(function($val)use($target,$propTodayColl){
                $val->amt_trg = $target->where("ulb_id",$val->ulb_id)->sum("amt_prop_trg");
                $val->balance = $val->amt_trg - $val->amount;
                $val->coll = $val->amount;
                $val->to_day_coll = $propTodayColl->where("ulb_id",$val->ulb_id)->sum("amount");
                return $val;
            });
            
            $waterColl = waterTran::select(DB::raw("SUM(water_trans.amount) AS amount,water_trans.ulb_id, ulb_masters.ulb_name"))
                        ->join("ulb_masters","ulb_masters.id","water_trans.ulb_id")
                        ->where(DB::raw("upper(water_trans.tran_type)"),str::upper("Demand Collection"))
                        ->whereIn("water_trans.status",[1,2]) 
                        ->whereIN("water_trans.ulb_id",$target->pluck("ulb_id")->unique())                       
                        ->groupBy("water_trans.ulb_id")
                        ->groupBy("ulb_masters.ulb_name");
            $waterTodayColl = waterTran::select(DB::raw("SUM(water_trans.amount) AS amount,water_trans.ulb_id, ulb_masters.ulb_name"))
                        ->join("ulb_masters","ulb_masters.id","water_trans.ulb_id")
                        ->where(DB::raw("upper(water_trans.tran_type)"),str::upper("Demand Collection"))
                        ->whereIn("water_trans.status",[1,2])  
                        ->whereIN("water_trans.ulb_id",$target->pluck("ulb_id")->unique())                      
                        ->groupBy("water_trans.ulb_id")
                        ->groupBy("ulb_masters.ulb_name");
            $waterTodayColl =$waterTodayColl->where("water_trans.tran_date",$toDaye)->get();
            $waterColl = $waterColl->whereBetween("water_trans.tran_date",[$fromDate,$uptoDate])->get()->map(function($val)use($target,$waterTodayColl){
                $val->amt_trg = $target->where("ulb_id",$val->ulb_id)->sum("amt_water_trg");
                $val->balance = $val->amt_trg - $val->amount;
                $val->coll = $val->amount;
                $val->to_day_coll = $waterTodayColl->where("ulb_id",$val->ulb_id)->sum("amount");
                return $val;
            });

            $tradeColl = tradeTransaction::select(DB::raw("SUM(trade_transactions.paid_amount) AS amount,trade_transactions.ulb_id, ulb_masters.ulb_name"))
                        ->join("ulb_masters","ulb_masters.id","trade_transactions.ulb_id")
                        ->whereIn("trade_transactions.status",[1,2])                        
                        ->whereIN("trade_transactions.ulb_id",$target->pluck("ulb_id")->unique())
                        ->groupBy("trade_transactions.ulb_id")
                        ->groupBy("ulb_masters.ulb_name");
            $tradeTodayColl = tradeTransaction::select(DB::raw("SUM(trade_transactions.paid_amount) AS amount,trade_transactions.ulb_id, ulb_masters.ulb_name"))
                            ->join("ulb_masters","ulb_masters.id","trade_transactions.ulb_id")
                            ->whereIn("trade_transactions.status",[1,2])
                            ->whereIN("trade_transactions.ulb_id",$target->pluck("ulb_id")->unique())
                            ->groupBy("trade_transactions.ulb_id")
                            ->groupBy("ulb_masters.ulb_name");
            $tradeTodayColl =$tradeTodayColl->where("trade_transactions.tran_date",$toDaye)->get();
            $tradeColl = $tradeColl->whereBetween("trade_transactions.tran_date",[$fromDate,$uptoDate])->get()->map(function($val)use($target,$tradeTodayColl){
                $val->amt_trg = $target->where("ulb_id",$val->ulb_id)->sum("amt_trade_trg");
                $val->balance = $val->amt_trg - $val->amount;
                $val->coll = $val->amount;
                $val->to_day_coll = $tradeTodayColl->where("ulb_id",$val->ulb_id)->sum("amount");
                return $val;
            });

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));            
            $final["target"]= [
                "property"=> $target->sum("amt_prop_trg"),
                "water"   => $target->sum("amt_water_trg"),
                "trade" => $target->sum("amt_trade_trg"),
                "total" => $target->sum("amt_prop_trg")+$target->sum("amt_water_trg")+$target->sum("amt_trade_trg"),
            ];
            $final["coll"]= [
                "property"=> $propColl->sum("amount"),
                "water"   => $waterColl->sum("amount"),
                "trade" => $tradeColl->sum("amount"),
                "total" => $propColl->sum("amount") + $waterColl->sum("amount")+$tradeColl->sum("amount"),
            ];
            $final["balence"]= [
                "property"=> $final["target"]["property"]-$final["coll"]["property"],
                "water"   => $final["target"]["water"]-$final["coll"]["water"],
                "trade" => $final["target"]["trade"]-$final["coll"]["trade"],
                "total" => $final["target"]["total"]-$final["coll"]["total"],
            ];
            $final["today"]= [
                "property"=> $propTodayColl->sum("amount"),
                "water"   => $waterTodayColl->sum("amount"),
                "trade" => $tradeTodayColl->sum("amount"),
                "total" => $propTodayColl->sum("amount") + $waterTodayColl->sum("amount")+$tradeTodayColl->sum("amount"),
            ];
            $data["target"]=$final;
            $data["dtl"]=$target->map(function($val)use($propColl,$waterColl,$tradeColl){
                $val->prop_demand = $val->amt_prop_trg;
                $val->water_demand = $val->amt_water_trg;
                $val->trade_demand = $val->amt_trade_trg;
                $val->total_demand = $val->amt_prop_trg + $val->amt_water_trg + $val->amt_trade_trg;

                $val->prop_coll = $propColl->where("ulb_id",$val->ulb_id)->sum("amount") ;
                $val->water_coll = $waterColl->where("ulb_id",$val->ulb_id)->sum("amount") ;
                $val->trade_coll = $tradeColl->where("ulb_id",$val->ulb_id)->sum("amount") ;
                $val->total_coll = $propColl->where("ulb_id",$val->ulb_id)->sum("amount") + $waterColl->where("ulb_id",$val->ulb_id)->sum("amount") + $tradeColl->where("ulb_id",$val->ulb_id)->sum("amount");

                $val->prop_balance = $val->prop_demand -$val->prop_coll;
                $val->water_balance = $val->water_demand -$val->water_coll;
                $val->trade_balance = $val->trade_demand -$val->trade_coll;
                $val->total_balance = $val->total_demand -$val->total_coll;

                $val->prop_today_coll = $propColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll") ;
                $val->water_today_coll = $waterColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll") ;
                $val->trade_today_coll = $tradeColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll") ;
                $val->total_today_coll = $propColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll") + $waterColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll") + $tradeColl->where("ulb_id",$val->ulb_id)->sum("to_day_coll");

                $val->prop_per = number_format(($val->prop_coll   / (($val->prop_demand > 0) ? $val->prop_demand : 1 ))*100,1) ;
                $val->water_per = number_format(($val->water_coll / (($val->water_demand > 0) ? $val->water_demand : 1 ))*100,1);
                $val->trade_per = number_format(($val->trade_coll / (($val->trade_demand > 0) ? $val->trade_demand :1 ))*100,1);
                $val->total_per = number_format(($val->total_coll / (($val->total_demand > 0) ? $val->total_demand :1 ))*100,1);

                return $val;
            });
            $data["fiYear"] =$fiYear;
            $data["total"] =[
                "ulb_name"=>"Total",
                "prop_demand"=>$data["dtl"]->sum("prop_demand"),
                "water_demand"=>$data["dtl"]->sum("water_demand"),
                "trade_demand"=>$data["dtl"]->sum("trade_demand"),
                "total_demand"=>$data["dtl"]->sum("total_demand"),

                "prop_coll"=>$data["dtl"]->sum("prop_coll"),
                "water_coll"=>$data["dtl"]->sum("water_coll"),
                "trade_coll"=>$data["dtl"]->sum("trade_coll"),
                "total_coll"=>$data["dtl"]->sum("total_coll"),

                "prop_balance"=>$data["dtl"]->sum("prop_balance"),
                "water_balance"=>$data["dtl"]->sum("water_balance"),
                "trade_balance"=>$data["dtl"]->sum("trade_balance"),
                "total_balance"=>$data["dtl"]->sum("total_balance"),

                "prop_today_coll"=>$data["dtl"]->sum("prop_today_coll"),
                "water_today_coll"=>$data["dtl"]->sum("water_today_coll"),
                "trade_today_coll"=>$data["dtl"]->sum("trade_today_coll"),
                "total_today_coll"=>$data["dtl"]->sum("total_today_coll"),

                "prop_per"=>$data["dtl"]->sum("prop_per"),
                "water_per"=>$data["dtl"]->sum("water_per"),
                "trade_per"=>$data["dtl"]->sum("trade_per"),
                "total_per"=>$data["dtl"]->sum("total_per"),
            ];
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);

        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), [], $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function ulbDCB(Request $request)
    {
        if(!$request->metaData)
        {
            $request->merge(["metaData" => ["ds11.1", 1.1, null, $request->getMethod(), null,]]);
        }
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        $validation = Validator::make($request->all(),[
            "fiYear" => "nullable||regex:/^\d{4}-\d{4}$/",
            "wardId" => "nullable|digits_between:1,9223372036854775807",
            "userId" => "nullable|digits_between:1,9223372036854775807",
            "ulbId" => "nullable|digits_between:1,9223372036854775807",
        ]);
        if($validation->fails())
        {
            return responseMsgs(false, "given Data invalid", $validation->errors(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }

        try{
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = null;
            $wardId = null;
            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            DB::enableQueryLog();
            $from = "
                FROM ulb_masters 
                LEFT JOIN(
                    SELECT prop_properties.ulb_id,
                    COUNT
                        (DISTINCT (
                            CASE WHEN prop_demands.due_date BETWEEN  '$fromDate' AND '$uptoDate'  then prop_demands.property_id
                            END)
                        ) as current_demand_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        COUNT
                            (DISTINCT (
                                CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.property_id
                                END)
                            ) as arrear_demand_hh,
                        SUM(
                            CASE WHEN prop_demands.due_date<'$fromDate' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_demand
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    WHERE prop_demands.status =1 
                    ".($ulbId ? "AND prop_demands.ulb_id =$ulbId":"")."
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_demands.due_date<='$uptoDate'
                        AND  char_length(prop_properties.new_holding_no)>0 AND prop_properties.status = 1
                    GROUP BY prop_properties.ulb_id
                )demands ON demands.ulb_id = ulb_masters.id
                LEFT JOIN (
                    SELECT prop_properties.ulb_id,
                    COUNT
                        (DISTINCT (
                            CASE WHEN prop_demands.due_date BETWEEN  '$fromDate' AND '$uptoDate'  then prop_demands.property_id
                            END)
                        ) as current_collection_hh,

                        COUNT(DISTINCT(prop_properties.id)) AS collection_from_no_of_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                    ELSE 0
                                    END
                        ) AS current_collection,

                        COUNT
                            (DISTINCT (
                                CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.property_id
                                END)
                            ) as arrear_collection_hh,

                        SUM(
                            CASE when prop_demands.due_date <'$fromDate' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                    ".($ulbId ? "AND prop_demands.ulb_id =$ulbId":"")."
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                        AND  char_length(prop_properties.new_holding_no)>0 AND prop_properties.status = 1
                    GROUP BY prop_properties.ulb_id
                )collection ON collection.ulb_id = ulb_masters.id
                LEFT JOIN ( 
                    SELECT prop_properties.ulb_id,
                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                    ".($ulbId ? "AND prop_demands.ulb_id =$ulbId":"")."
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                        AND  char_length(prop_properties.new_holding_no)>0 AND prop_properties.status = 1
                    GROUP BY prop_properties.ulb_id
                )prev_collection ON prev_collection.ulb_id =  ulb_masters.id                 
                WHERE  1=1 ".($ulbId ? " AND ulb_masters.id = $ulbId":"" ). 
               " GROUP BY ulb_masters.id,ulb_masters.ulb_name           
            ";
            $select = "SELECT ulb_masters.id,ulb_masters.ulb_name AS ulb_name,
                            SUM(COALESCE(demands.current_demand_hh, 0::numeric)) AS current_demand_hh,   
                            SUM(COALESCE(demands.arrear_demand_hh, 0::numeric)) AS arrear_demand_hh,
                            SUM(COALESCE(collection.current_collection_hh, 0::numeric)) AS current_collection_hh,   
                            SUM(COALESCE(collection.arrear_collection_hh, 0::numeric)) AS arrear_collection_hh,
                            SUM(COALESCE(collection.collection_from_no_of_hh, 0::numeric)) AS collection_from_hh,
                            
                            round(SUM(((collection.arrear_collection_hh ::numeric) / (case when demands.arrear_demand_hh > 0 then demands.arrear_demand_hh else 1 end))*100)) AS arrear_hh_eff,
                            round(SUM(((collection.current_collection_hh ::numeric) / (case when demands.current_demand_hh > 0 then demands.current_demand_hh else 1 end))*100)) AS current_hh_eff,

                            round(SUM(COALESCE(
                                COALESCE(demands.current_demand_hh, 0::numeric) 
                                - COALESCE(collection.collection_from_no_of_hh, 0::numeric), 0::numeric
                            ))) AS balance_hh,                       
                            round(SUM(COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ))) AS arrear_demand,
                    
                            round(SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric))) AS previous_collection,
                            round(SUM(COALESCE(demands.current_demand, 0::numeric))) AS current_demand,
                            round(SUM(COALESCE(collection.arrear_collection, 0::numeric))) AS arrear_collection,
                            round(SUM(COALESCE(collection.current_collection, 0::numeric))) AS current_collection,
                    
                            round(SUM((COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                                )))AS old_due,
                    
                            round(SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)))) AS current_due,

                            round(SUM((COALESCE(demands.current_demand_hh, 0::numeric) - COALESCE(collection.current_collection_hh, 0::numeric)))) AS current_balance_hh,
                            round(SUM((COALESCE(demands.arrear_demand_hh, 0::numeric) - COALESCE(collection.arrear_collection_hh, 0::numeric)))) AS arrear_balance_hh,

                            round(SUM(((collection.arrear_collection ::numeric) / (case when demands.arrear_demand > 0 then demands.arrear_demand else 1 end))*100)) AS arrear_eff,
                            round(SUM(((collection.current_collection ::numeric) / (case when demands.current_demand > 0 then demands.current_demand else 1 end))*100)) AS current_eff,

                            round(SUM((
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.current_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            ))) AS outstanding                                 
            ";
            $dcb = DB::select($select . $from);
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $dcb, $apiId, $version, responseTime(), $action, $deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), [], $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
}
