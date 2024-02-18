<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UlbMaster;
use App\Models\MplYearlyReport;
use App\Models\Property\PropDemand;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster as ModelsUlbMaster;
use App\Models\Water\WaterTran;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Auth;
use App\Traits\Property\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Geocoder\Geocoder;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Illuminate\Support\Facades\Http;

#------------date 13/03/2023 -------------------------------------------------------------------------
#   Code By Sandeep Bara
#   Payment Mode Wise Collection Report

class ReportController extends Controller
{
    use Auth;
    use Report;

    private $Repository;
    private $_common;
    public function __construct(IReport $TradeRepository)
    {
        DB::enableQueryLog();
        $this->Repository = $TradeRepository;
        $this->_common = new CommonFunction();
    }

    public function collectionReport(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->collectionReport($request);
    }

    public function safCollection(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012402", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safCollection($request);
    }

    public function safPropIndividualDemandAndCollection(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "key" => "nullable|regex:/^[^<>{};:.,~!?@#$%^=&*\"]*$/i",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012403", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safPropIndividualDemandAndCollection($request);
    }

    public function levelwisependingform(Request $request)
    {
        $request->request->add(["metaData" => ["012404", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelwisependingform($request);
    }

    public function levelformdetail(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "roleId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012405", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelformdetail($request);
    }

    public function levelUserPending(Request $request)
    {
        $request->validate(
            [
                "roleId" => "required|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012406", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelUserPending($request);
    }

    public function userWiseLevelPending(Request $request)
    {
        $request->validate(
            [
                "userId" => "required|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012407", 1.1, null, $request->getMethod(), null,]]);

        $refUser        = Auth()->user();
        $refUserId      = $refUser->id;
        $ulbId          = $refUser->ulb_id;
        $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        if ($request->ulbId) {
            $ulbId = $request->ulbId;
        }

        $respons =  $this->levelformdetail($request);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        $roles = ($this->_common->getUserRoll($request->userId, $ulbId, $safWorkFlow));
        $respons = json_decode(json_encode($respons), true);
        if ($respons["original"]["status"]) {
            $respons["original"]["data"]["data"] = collect($respons["original"]["data"]["data"])->map(function ($val) use ($roles) {
                $val["role_name"] = $roles->role_name ?? "";
                $val["role_id"] = $roles->role_id ?? 0;
                return $val;
            });
        }
        return responseMsgs($respons["original"]["status"], $respons["original"]["message"], $respons["original"]["data"], $apiId, $version, $queryRunTime, $action, $deviceId);
    }

    public function userWiseWardWireLevelPending(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "required|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012408", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->userWiseWardWireLevelPending($request);
    }

    public function safSamFamGeotagging(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012409", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safSamFamGeotagging($request);
    }

    public function PropPaymentModeWiseSummery(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012421", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropPaymentModeWiseSummery($request);
    }

    public function PaymentModeWiseSummery(Request $request)
    {
        $request->merge(["metaData" => ["012422", 1.1, null, $request->getMethod(), null,]]);
        $validation = Validator::make($request->all(), [
            "fromDate" => "required|date|date_format:Y-m-d",
            "uptoDate" => "required|date|date_format:Y-m-d",
            "ulbId" => "nullable|digits_between:1,9223372036854775807",
            "wardId" => "nullable|digits_between:1,9223372036854775807",
            "paymentMode" => "nullable",
            "userId" => "nullable|digits_between:1,9223372036854775807",
        ]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        if ($validation->fails()) {
            return responseMsgs(false, "given Data invalid", $validation->errors(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
        return $this->Repository->PropPaymentModeWiseSummery($request);
    }

    public function SafPaymentModeWiseSummery(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012423", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->SafPaymentModeWiseSummery($request);
    }

    public function PropDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012424", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDCB($request);
    }

    public function PropWardWiseDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012425", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropWardWiseDCB($request);
    }

    public function PropFineRebate(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012426", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropFineRebate($request);
    }

    public function PropDeactedList(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "nullable|date|date_format:Y-m-d",
                "uptoDate" => "nullable|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012427", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDeactedList($request);
    }


    //========================================================================================================
    // Modification By : Mrinal Kumar
    // Date : 11-03-2023

    /**
     * | Ward wise holding report
     */
    public function wardWiseHoldingReport(Request $request)
    {
        $mPropDemand = new PropDemand();
        $wardMstrId = $request->wardMstrId;
        $ulbId = authUser($request)->ulb_id;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $start = Carbon::createFromDate($request->year, 4, 1);

        $fromDate = $start->format('Y-m-d');
        if ($currentMonth > 3) {
            $end = Carbon::createFromDate($currentYear + 1, 3, 31);
            $toDate = $end->format('Y-m-d');
        } else
            $toDate = ($currentYear . '-03-31');

        $mreq = new Request([
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "ulbId" => $ulbId,
            "wardMstrId" => $wardMstrId,
            "perPage" => $request->perPage
        ]);

        $data = $mPropDemand->wardWiseHolding($mreq);

        $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
        return responseMsgs(true, "Ward Wise Holding Data!", $data, '012410', '1.1', $queryRunTime, 'Post', '');
    }

    /**
     * | List of financial year
     */
    public function listFY(Request $request)
    {
        $currentYear = Carbon::now()->year;
        $financialYears = array();
        $currentYear = date('Y');

        for ($year = 2015; $year <= $currentYear; $year++) {
            $startOfYear = $year . '-04-01'; // Financial year starts on April 1st
            $endOfYear = ($year + 1) . '-03-31'; // Financial year ends on March 31st
            $financialYear = getFinancialYear($startOfYear, 2015); // Calculate financial year and add a label
            $financialYears[] = $financialYear;
        }
        return responseMsgs(true, "Financial Year List", array_reverse($financialYears), '012411', '01', '382ms-547ms', 'Post', '');
    }

    /**
     * | Printing of bulk receipt
     */
    public function bulkReceipt(Request $req, iSafRepository $safRepo)
    {
        $req->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date',
            'tranType' => 'required|In:Property,Saf',
            'userId' => 'required|numeric',
        ]);
        try {
            $fromDate = $req->fromDate;
            $toDate = $req->toDate;
            $userId = $req->userId;
            $tranType = $req->tranType;
            $mpropTransaction = new PropTransaction();
            $holdingCotroller = new HoldingTaxController($safRepo);
            $activeSafController = new ActiveSafController($safRepo);
            $propReceipts = collect();
            $receipts = collect();

            $transaction = $mpropTransaction->tranDtl($userId, $fromDate, $toDate);

            if ($tranType == 'Property')
                $data = $transaction->whereNotNull('property_id')->get();

            if ($tranType == 'Saf')
                $data = $transaction->whereNotNull('saf_id')->get();

            $tranNos = collect($data)->pluck('tran_no');

            foreach ($tranNos as $tranNo) {
                $mreq = new Request(
                    ["tranNo" => $tranNo]
                );
                if ($tranType == 'Property')
                    $data = $holdingCotroller->propPaymentReceipt($mreq);

                if ($tranType == 'Saf')
                    $data = $activeSafController->generatePaymentReceipt($mreq);

                $propReceipts->push($data);
            }

            foreach ($propReceipts as $propReceipt) {
                $receipt = $propReceipt->original['data'];
                $receipts->push($receipt);
            }

            return responseMsgs(true, 'Bulk Receipt', remove_null($receipts), '012412', '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | GbSafCollection
     */
    public function gbSafCollection(Request $req)
    {
        $req->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
            ]
        );
        try {
            $fromDate = $req->fromDate;
            $uptoDate = $req->uptoDate;
            $perPage = $req->perPage ?? 5;
            $tbl1 = 'prop_active_safs';
            $officerTbl1 = 'prop_active_safgbofficers';
            $tbl2 = 'prop_safs';
            $officerTbl2 = 'prop_gbofficers';

            $first_query =  $this->gbSafCollectionQuery($tbl1, $fromDate, $uptoDate, $officerTbl1);
            $gbsafCollection = $this->gbSafCollectionQuery($tbl2, $fromDate, $uptoDate, $officerTbl2)
                ->union($first_query);

            if ($req->wardId)
                $gbsafCollection = $gbsafCollection->where('ward_mstr_id', $req->wardId);

            if ($req->paymentMode)
                $gbsafCollection = $gbsafCollection->where('payment_mode', $req->paymentMode);

            $list = $gbsafCollection->paginate($perPage);
            return $list;
            return responseMsgs(true, "GB Saf Collection!", $list, '012413', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Ward wise Individual Property Demand
     */
    public function propIndividualDemandCollection(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012414", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->propIndividualDemandCollection($request);
    }


    /**
     * | GBSAF Ward wise Individual Demand
     */
    public function gbsafIndividualDemandCollection(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012415", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->gbsafIndividualDemandCollection($request);
    }

    /**
     * | Not paid from 2016-2017
     */
    public function notPaidFrom2016(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012416", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->notPaidFrom2016($request);
    }

    /**
     * | Not paid from 2019-2017
     */
    public function previousYearPaidButnotCurrentYear(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["012417", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->previousYearPaidButnotCurrentYear($request);
    }

    /**
     * | Dcb Pie Chart
     */
    public function dcbPieChart(Request $request)
    {
        $validator = Validator::make($request->all(), ['ulbId' => 'nullable|int']);
        if ($validator->fails()) {
            return validationError($validator);
        }
        return $this->Repository->dcbPieChart($request);
    }

    /**
     * | 
     */
    public function propSafCollection(Request $request)
    {
        $propCollection = null;
        $safCollection = null;
        $gbsafCollection = null;
        $proptotalData = 0;
        $proptotal = 0;
        $saftotal = 0;
        $gbsaftotal = 0;
        $saftotalData = 0;
        $gbsaftotalData = 0;
        $collectionTypes = $request->collectionType;
        $perPage = $request->perPage ?? 5;

        if ($request->user == 'tc') {
            $userId = authUser($request)->id;
            $request->merge(["userId" => $userId]);
        }

        foreach ($collectionTypes as $collectionType) {
            if ($collectionType == 'property') {
                $propCollection =   $this->collectionReport($request);
                $proptotal = $propCollection->original['data']['totalAmount'];
                $proptotalData = $propCollection->original['data']['total'];
                $propCollection = $propCollection->original['data']['data'];
            }

            if ($collectionType == 'saf') {
                $safCollection = $this->safCollection($request);
                $saftotal = $safCollection->original['data']['totalAmount'];
                $saftotalData = $safCollection->original['data']['total'];
                $safCollection = $safCollection->original['data']['data'];
            }

            if ($collectionType == 'gbsaf') {
                $gbsafCollection = $this->gbSafCollection($request);
                $gbsaftotalData = $gbsafCollection->toarray()['total'];
                $gbsafCollection = $gbsafCollection->toarray()['data'];
                $gbsaftotal = collect($gbsafCollection)->sum('amount');
            }
        }
        $currentPage = $request->page ?? 1;
        $details = collect($propCollection)->merge($safCollection)->merge($gbsafCollection);

        $a = round($proptotalData / $perPage);
        $b = round($saftotalData / $perPage);
        $c = round($gbsaftotalData / $perPage);
        $data['current_page'] = $currentPage;
        $data['total'] = $proptotalData + $saftotalData + $gbsaftotalData;
        $data['totalAmt'] = round($proptotal + $saftotal + $gbsaftotal);
        $data['last_page'] = max($a, $b, $c);
        $data['data'] = $details;

        return responseMsgs(true, "", $data, "", "012419", "", "post", $request->deviceId);
    }

    /**
     * | Holding Wise Rebate & Penalty
     */
    public function rebateNpenalty(Request $request)
    {
        return $this->Repository->rebateNpenalty($request);
    }

    /**
     * | Mpl Report
     */

     public function mplReport(Request $request)
     {
         try {
             $ulbId = $request->ulbId ;
             $fyear = getFY();
             $fyArr = explode("-", $fyear);
             $privYear = ($fyArr[0] - 1) . "-" . ($fyArr[1] - 1);
             $prevYearData =  DB::connection('pgsql_reports')
                 ->table('mpl_yearly_reports')
                 ->where("fyear", $privYear) ;
                // ->where(DB::raw("CAST(created_at AS DATE)"),Carbon::now()->format("Y-m-d"));
             $currentYearData =  DB::connection('pgsql_reports')
                 ->table('mpl_yearly_reports')
                 ->where("fyear", $fyear);
                // ->where(DB::raw("CAST(created_at AS DATE)"),Carbon::now()->format("Y-m-d"));
            if($ulbId)
            {
                $currentYearData = $currentYearData->where("ulb_id",$ulbId);
                $prevYearData = $prevYearData->where("ulb_id",$ulbId);
            }
            $prevYearData = $prevYearData->get()->map(function($val){
                $val->top_area_property_transaction_ward_count_total = 
                (
                    $val->top_area_property_transaction_ward1_count + 
                    $val->top_area_property_transaction_ward2_count +
                    $val->top_area_property_transaction_ward3_count +
                    $val->top_area_property_transaction_ward4_count +
                    $val->top_area_property_transaction_ward5_count 
                );
                $val->top_area_saf_ward_count_total = 
                (
                    $val->top_area_saf_ward1_count + 
                    $val->top_area_saf_ward2_count +
                    $val->top_area_saf_ward3_count +
                    $val->top_area_saf_ward4_count +
                    $val->top_area_saf_ward5_count 
                );
                $val->top_defaulter_ward_count_total = 
                (
                    $val->top_defaulter_ward1_count + 
                    $val->top_defaulter_ward2_count +
                    $val->top_defaulter_ward3_count +
                    $val->top_defaulter_ward4_count +
                    $val->top_defaulter_ward5_count 
                );
                return $val;
            });
            $currentYearData = $currentYearData->get()->map(function($val){
                $val->top_area_property_transaction_ward_count_total = 
                (
                    $val->top_area_property_transaction_ward1_count +
                    $val->top_area_property_transaction_ward2_count +
                    $val->top_area_property_transaction_ward3_count +
                    $val->top_area_property_transaction_ward4_count +
                    $val->top_area_property_transaction_ward5_count 
                );
                $val->top_area_saf_ward_count_total = 
                (
                    $val->top_area_saf_ward1_count + 
                    $val->top_area_saf_ward2_count +
                    $val->top_area_saf_ward3_count +
                    $val->top_area_saf_ward4_count +
                    $val->top_area_saf_ward5_count 
                );
                $val->top_defaulter_ward_count_total = 
                (
                    $val->top_defaulter_ward1_count + 
                    $val->top_defaulter_ward2_count +
                    $val->top_defaulter_ward3_count +
                    $val->top_defaulter_ward4_count +
                    $val->top_defaulter_ward5_count 
                );
                return $val;
            });
            // dd( $data['Property Zone Collection']['a_zone_name'] =  ((collect($currentYearData)->whereNotNull("a_zone_name")->where("a_zone_name","<>","")->first())->a_zone_name),$currentYearData);
            $topFiveUlbs = collect();
            $couter = 0;
            foreach( collect(($currentYearData)->sortByDesc("top_area_property_transaction_ward_count_total")) as $val)
            {
                if($couter>=5)
                {
                    break;
                }
                ++$couter;                
                $topFiveUlbs->push($val);
            }
            $topFiveSafCount = collect();
            $couter = 0;
            foreach( collect(($currentYearData)->sortByDesc("top_area_saf_ward_count_total")) as $val)
            {
                if($couter>=5)
                {
                    break;
                }
                ++$couter;                
                $topFiveSafCount->push($val);
            }
            $topFivedefaulterCount = collect();
            $couter = 0;
            foreach( collect(($currentYearData)->sortByDesc("top_defaulter_ward_count_total")) as $val)
            {
                if($couter>=5)
                {
                    break;
                }
                ++$couter;                
                $topFivedefaulterCount->push($val);
            }
        
             #_Assessment Categories ??
            $data['Assessment Categories']['total_assessment']  = collect($currentYearData)->sum("total_assessment") ?? 0; 
            $data['Prop Categories']['total_assessment']  = collect($currentYearData)->sum("total_property") ?? 0;
            $data['Prop Categories']['total_residential_props'] = collect($currentYearData)->sum("total_residential_props") ?? 0;
            $data['Prop Categories']['total_commercial_props']  = collect($currentYearData)->sum("total_commercial_props") ?? 0;
            $data['Prop Categories']['total_govt_props']  = collect($currentYearData)->sum("total_govt_props") ?? 0;
            $data['Prop Categories']['total_industrial_props'] = collect($currentYearData)->sum("total_industrial_props") ?? 0;
            $data['Prop Categories']['total_religious_props'] = collect($currentYearData)->sum("total_religious_props") ?? 0;
            $data['Prop Categories']['total_trust_props'] = collect($currentYearData)->sum("total_trust_props") ?? 0; 
            $data['Prop Categories']['total_mixed_props'] = collect($currentYearData)->sum("total_mixed_props") ?? 0; 
            $data['Prop Categories']['vacand']  = collect($currentYearData)->sum("vacant_property") ?? 0;

            #prop_dcb
            round(collect($currentYearData)->sum("current_year_cash_collection") / 10000000, 2);
            $data['Prop DCB']['prop_current_demand']  = round(collect($currentYearData)->sum("prop_current_demand")/ 10000000, 2);
            $data['Prop DCB']['prop_arrear_demand']  = round(collect($currentYearData)->sum("prop_arrear_demand")/ 10000000, 2);
            $data['Prop DCB']['prop_current_collection']  = round(collect($currentYearData)->sum("prop_current_collection")/ 10000000, 2);
            $data['Prop DCB']['prop_arrear_collection']  = round(collect($currentYearData)->sum("prop_arrear_collection")/ 10000000, 2);
            $data['Prop DCB']['prop_outsatnding_current_demand']  = round(collect($currentYearData)->sum("prop_outsatnding_current_demand")/ 10000000, 2);
            $data['Prop DCB']['prop_current_collection_efficiency']  = collect($currentYearData)->sum("prop_current_collection_efficiency");
            $data['Prop DCB']['prop_arrear_collection_efficiency']  = collect($currentYearData)->sum("prop_arrear_collection_efficiency");
          
            #_Ownership ??
            $data['Ownership']['total_ownership'] = collect($currentYearData)->sum("total_property") ?? 0;
            $data['Ownership']['owned_property']  = collect($currentYearData)->sum("owned_property") ?? 0;
            $data['Ownership']['rented_property'] = collect($currentYearData)->sum("rented_property") ?? 0;
            $data['Ownership']['mixed_property']  = collect($currentYearData)->sum("mixed_property") ?? 0;
            $data['Ownership']['vacant_property'] = collect($currentYearData)->sum("vacant_property") ?? 0;

            // $data['Property']['prop_current_demand']    = round(($currentYearData->prop_current_demand ?? 0) / 10000000, 2);
            // $data['Property']['prop_arrear_demand']    = round(($currentYearData->prop_arrear_demand ?? 0) / 10000000, 2);
            // $data['Property']['prop_total_demand']    = round(($currentYearData->prop_total_demand ?? 0) / 10000000, 2);
            
            $data['Property Zone Collection']['a_zone_name'] =  ((collect($currentYearData)->whereNotNull("a_zone_name")->where("a_zone_name","<>","")->first())->a_zone_name ?? "") ;
           
            $data['Property Zone Collection']['a_prop_total_hh'] = collect($currentYearData)->sum("a_prop_total_hh");
            $data['Property Zone Collection']['a_prop_total_amount'] = collect($currentYearData)->sum("a_prop_total_amount");
            $data['Property Zone Collection']['b_zone_name']  = ((collect($currentYearData)->whereNotNull("b_zone_name")->where("b_zone_name","<>","")->first())->b_zone_name ?? "") ;
            $data['Property Zone Collection']['b_prop_total_hh'] = collect($currentYearData)->sum("b_prop_total_hh");
            $data['Property Zone Collection']['b_prop_total_amount'] = collect($currentYearData)->sum("b_prop_total_amount");
                                        
            
            //  #_Top Areas Property Transactions 
            //  /**

            //   include ward no
            //   */
            $data['Top Areas Property Transactions']['ward1_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_property_transaction_ward1_count") ?? 0) : collect(($topFiveUlbs[0]??[]))["top_area_property_transaction_ward_count_total"]??0;
            
             $data['Top Areas Property Transactions']['ward1_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_property_transaction_ward1_name ?? "") : collect(($topFiveUlbs[0]??[]))["ulb_name"]??"";

             $data['Top Areas Property Transactions']['ward2_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_property_transaction_ward2_count") ?? 0) : collect(($topFiveUlbs[1]??[]))["top_area_property_transaction_ward_count_total"]??0;
                        
             $data['Top Areas Property Transactions']['ward2_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_property_transaction_ward2_name ?? "") : collect(($topFiveUlbs[1]??[]))["ulb_name"]??"";

             $data['Top Areas Property Transactions']['ward3_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_property_transaction_ward3_count") ?? 0) : collect(($topFiveUlbs[1]??[]))["top_area_property_transaction_ward_count_total"]??0;

             $data['Top Areas Property Transactions']['ward3_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_property_transaction_ward3_name ?? "") : collect(($topFiveUlbs[2]??[]))["ulb_name"]??"";
             $data['Top Areas Property Transactions']['ward4_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_property_transaction_ward4_count") ?? 0) : collect(($topFiveUlbs[1]??[]))["top_area_property_transaction_ward_count_total"]??0;

             $data['Top Areas Property Transactions']['ward4_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_property_transaction_ward4_name ?? "") : collect(($topFiveUlbs[3]??[]))["ulb_name"]??"";
             $data['Top Areas Property Transactions']['ward5_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_property_transaction_ward5_count") ?? 0) : collect(($topFiveUlbs[1]??[]))["top_area_property_transaction_ward_count_total"]??0;

             $data['Top Areas Property Transactions']['ward5_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_property_transaction_ward5_name ?? "") : collect(($topFiveUlbs[4]??[]))["ulb_name"]??"";


             //  #_Top Areas saf count 
            //  /**
            //   include ward no
            //   */
            $data['Top Areas Saf']['ward1_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_saf_ward1_count") ?? 0) : collect(($topFiveSafCount[0]??[]))["top_area_saf_ward_count_total"]??0;
            
             $data['Top Areas Saf']['ward1_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_saf_ward1_name ?? "") : collect(($topFiveSafCount[0]??[]))["ulb_name"]??"";

             $data['Top Areas Saf']['ward2_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_saf_ward2_count") ?? 0) : collect(($topFiveSafCount[1]??[]))["top_area_saf_ward_count_total"]??0;
                        
             $data['Top Areas Saf']['ward2_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_saf_ward2_name ?? "") : collect(($topFiveSafCount[1]??[]))["ulb_name"]??"";

             $data['Top Areas Saf']['ward3_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_saf_ward3_count") ?? 0) : collect(($topFiveSafCount[2]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas Saf']['ward3_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_saf_ward3_name ?? "") : collect(($topFiveSafCount[2]??[]))["ulb_name"]??"";
             $data['Top Areas Saf']['ward4_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_saf_ward4_count") ?? 0) : collect(($topFiveSafCount[3]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas Saf']['ward4_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_saf_ward4_name ?? "") : collect(($topFiveSafCount[3]??[]))["ulb_name"]??"";
             $data['Top Areas Saf']['ward5_count'] = $ulbId ?  (collect($currentYearData)->sum("top_area_saf_ward5_count") ?? 0) : collect(($topFiveSafCount[4]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas Saf']['ward5_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_area_saf_ward5_name ?? "") : collect(($topFiveSafCount[4]??[]))["ulb_name"]??"";
            
            //  #_Top Areas defaulter count 
            //  /** 
            //   include ward no
            //   */
            $data['Top Areas defaulter']['ward1_count'] = $ulbId ?  (collect($currentYearData)->sum("top_defaulter_ward1_count") ?? 0) : collect(($topFivedefaulterCount[0]??[]))["top_area_saf_ward_count_total"]??0;
            
             $data['Top Areas defaulter']['ward1_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward1_name ?? "") : collect(($topFivedefaulterCount[0]??[]))["ulb_name"]??"";

             $data['Top Areas defaulter']['ward1_amount']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward1_amount ?? "") : collect(($topFivedefaulterCount[0]??[]))["top_defaulter_ward1_amount"]??"";

             $data['Top Areas defaulter']['ward2_count'] = $ulbId ?  (collect($currentYearData)->sum("top_defaulter_ward2_count") ?? 0) : collect(($topFivedefaulterCount[1]??[]))["top_area_saf_ward_count_total"]??0;
                        
             $data['Top Areas defaulter']['ward2_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward2_name ?? "") : collect(($topFivedefaulterCount[1]??[]))["ulb_name"]??"";

             $data['Top Areas defaulter']['ward2_amount']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward2_amount ?? "") : collect(($topFivedefaulterCount[1]??[]))["top_defaulter_ward2_amount"]??"";

             $data['Top Areas defaulter']['ward3_count'] = $ulbId ?  (collect($currentYearData)->sum("top_defaulter_ward3_count") ?? 0) : collect(($topFivedefaulterCount[2]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas defaulter']['ward3_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward3_name ?? "") : collect(($topFivedefaulterCount[2]??[]))["ulb_name"]??"";

             $data['Top Areas defaulter']['ward3_amount']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward3_amount ?? "") : collect(($topFivedefaulterCount[2]??[]))["top_defaulter_ward3_amount"]??"";

             $data['Top Areas defaulter']['ward4_count'] = $ulbId ?  (collect($currentYearData)->sum("top_defaulter_ward4_count") ?? 0) : collect(($topFivedefaulterCount[3]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas defaulter']['ward4_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward4_name ?? "") : collect(($topFivedefaulterCount[3]??[]))["ulb_name"]??"";

             $data['Top Areas defaulter']['ward4_amount']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward4_amount ?? "") : collect(($topFivedefaulterCount[3]??[]))["top_defaulter_ward4_amount"]??"";

             $data['Top Areas defaulter']['ward5_count'] = $ulbId ?  (collect($currentYearData)->sum("top_defaulter_ward5_count") ?? 0) : collect(($topFivedefaulterCount[4]??[]))["top_area_saf_ward_count_total"]??0;

             $data['Top Areas defaulter']['ward5_name']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward5_name ?? "") : collect(($topFivedefaulterCount[4]??[]))["ulb_name"]??"";

             $data['Top Areas defaulter']['ward5_amount']  = 
             $ulbId ?  ((collect($currentYearData)->first())->top_defaulter_ward5_amount ?? "") : collect(($topFivedefaulterCount[4]??[]))["top_defaulter_ward5_amount"]??"";

            # Employee count
            $data['Employee count']['tc_count'] = collect($currentYearData)->sum("tc_count") ?? 0;
            $data['Employee count']['da_count'] = collect($currentYearData)->sum("da_count") ?? 0;
            $data['Employee count']['si_count'] = collect($currentYearData)->sum("si_count") ?? 0;
            $data['Employee count']['eo_count'] = collect($currentYearData)->sum("eo_count") ?? 0;
            $data['Employee count']['utc_count'] = collect($currentYearData)->sum("utc_count") ?? 0;
            $data['Employee count']['bo_count'] = collect($currentYearData)->sum("bo_count") ?? 0;

            #Payment status against property
            $data['Payment status against property']['total_unpaid_property'] = collect($currentYearData)->sum("total_unpaid_property") ?? 0;
            $data['Payment status against property']['total_paid_property'] = collect($currentYearData)->sum("total_paid_property") ?? 0;
            $data['Payment status against property']['current_unpaid_property'] = collect($currentYearData)->sum("current_unpaid_property") ?? 0;

            #_Payment Modes
            $data['Payment Modes']['current_year_cash_collection'] = round(collect($currentYearData)->sum("current_year_cash_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_upi_collection'] = round(collect($currentYearData)->sum("current_year_upi_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_card_collection'] = round(collect($currentYearData)->sum("current_year_card_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_cheque_collection'] = round(collect($currentYearData)->sum("current_year_cheque_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_dd_collection'] = round(collect($currentYearData)->sum("current_year_dd_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_neft_collection'] = round(collect($currentYearData)->sum("current_year_neft_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_rtgs_collection'] = round(collect($currentYearData)->sum("current_year_rtgs_collection") / 10000000, 2);
            $data['Payment Modes']['current_year_online_collection'] = round(collect($currentYearData)->sum("current_year_online_collection") / 10000000, 2);
            
 
             #trade
             $data['Trade']['tota_trade_licenses'] = collect($currentYearData)->sum("total_trade_licenses");
            $data['Trade']['total_trade_licenses_underprocess'] = collect($currentYearData)->sum("total_trade_licenses_underprocess");
            $data['Trade']['trade_current_cash_payment'] = round(collect($currentYearData)->sum("trade_current_cash_payment") / 10000000, 2);
            $data['Trade']['trade_current_cheque_payment'] = round(collect($currentYearData)->sum("trade_current_cheque_payment") / 10000000, 2);
            $data['Trade']['trade_current_dd_payment'] = round(collect($currentYearData)->sum("trade_current_dd_payment") / 10000000, 2);
            $data['Trade']['trade_current_card_payment'] = round(collect($currentYearData)->sum("trade_current_card_payment") / 10000000, 2);
            $data['Trade']['trade_current_neft_payment'] = round(collect($currentYearData)->sum("trade_current_neft_payment") / 10000000, 2);
            $data['Trade']['trade_current_rtgs_payment'] = round(collect($currentYearData)->sum("trade_current_rtgs_payment") / 10000000, 2);
            $data['Trade']['trade_current_online_payment'] = round(collect($currentYearData)->sum("trade_current_online_payment") / 10000000, 2);
            $data['Trade']['trade_current_online_counts'] = collect($currentYearData)->sum("trade_current_online_counts");
            $data['Trade']['trade_lastyear_cash_payment'] = round(collect($currentYearData)->sum("trade_lastyear_cash_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_cheque_payment'] = round(collect($currentYearData)->sum("trade_lastyear_cheque_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_dd_payment'] = round(collect($currentYearData)->sum("trade_lastyear_dd_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_neft_payment'] = round(collect($currentYearData)->sum("trade_lastyear_neft_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_rtgs_payment'] = round(collect($currentYearData)->sum("trade_lastyear_rtgs_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_online_payment'] = round(collect($currentYearData)->sum("trade_lastyear_online_payment") / 10000000, 2);
            $data['Trade']['trade_lastyear_online_counts'] = collect($currentYearData)->sum("trade_lastyear_online_counts");
            $data['Trade']['trade_renewal_less_then_1_year'] = collect($currentYearData)->sum("trade_renewal_less_then_1_year");
            $data['Trade']['trade_renewal_more_then_1_year'] = collect($currentYearData)->sum("trade_renewal_more_then_1_year");
            $data['Trade']['trade_renewal_more_then_1_year_and_less_then_5_years'] = collect($currentYearData)->sum("trade_renewal_more_then_1_year_and_less_then_5_years");
            $data['Trade']['trade_renewal_more_then_5_year'] = collect($currentYearData)->sum("trade_renewal_more_then_5_year");

        
             // #water
             $data['Water']['water_connection_underprocess'] = collect($currentYearData)->sum("water_connection_underprocess");
             $data['Water']['water_fix_connection_type'] = collect($currentYearData)->sum("water_fix_connection_type");
             $data['Water']['water_meter_connection_type'] = collect($currentYearData)->sum("water_meter_connection_type");
             $data['Water']['water_current_demand'] = round(collect($currentYearData)->sum("water_current_demand") / 10000000, 2); # in cr
             $data['Water']['water_arrear_demand'] = round(collect($currentYearData)->sum("water_arrear_demand") / 10000000, 2); # in cr
             
             $data['Water']['water_current_collection'] = round(collect($currentYearData)->sum("water_current_collection") / 10000000, 2); # in cr
             $data['Water']['water_arrear_collection'] = round(collect($currentYearData)->sum("water_arrear_collection") / 10000000, 2); # in cr
             $data['Water']['water_total_collection'] = round(collect($currentYearData)->sum("water_total_collection") / 10000000, 2); # in cr
            $data['Water']['water_current_collection_efficiency'] = collect($currentYearData)->sum("water_current_collection_efficiency");
            $data['Water']['water_arrear_collection_efficiency'] = collect($currentYearData)->sum("water_arrear_collection_efficiency");

 
             return responseMsgs(true, "Mpl Report", $data, "", 01, responseTime(), $request->getMethod(), $request->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, [$e->getMessage(),$e->getFile(),$e->getLine()], "", "", 01, responseTime(), $request->getMethod(), $request->deviceId);
         }
     }
   
    

   

    /**
     * | 
     */
    public function getLocality(Request $req)
    {
        // Replace 'YOUR_API_KEY' with your actual Google Maps API key
        $apiKey = 'AIzaSyCgO44E5p_UtCtJp1890McbBKeawwDIBe8';

        // Google Maps Geocoding API endpoint
        $apiUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

        // Make the API request
        $response = Http::get($apiUrl, [
            'latlng' => "{$req->latitude},{$req->longitude}",
            'key' => $apiKey,
        ]);

        // Parse the JSON response
        return $data = $response->json();

        // Check if the request was successful
        if ($response->successful()) {
            // Extract the locality (city) from the results
            foreach ($data['results'] as $result) {
                foreach ($result['address_components'] as $component) {
                    if (in_array('locality', $component['types'])) {
                        return $component['long_name'];
                    }
                }
            }
        }

        // If no locality is found or there's an error, return null or an appropriate response
        return null;
        return response()->json(['error' => 'Locality not found'], 404);
    }

   

    public function mplReportCollectionNew(Request $request)
    {
    try {
        $request->merge(["metaData" => ["012430", 1.1, null, $request->getMethod(), null]]);
        $validation = Validator::make($request->all(), [
            "ulbId" => "nullable|digits_between:1,9223372036854775807"
        ]);

        if ($validation->fails()) {
            return responseMsgs(false, "Ulb_Id validation failed", $validation->errors(), "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        }

        $ulbId = $request->ulbId;
        $currentDate = Carbon::now()->format("Y-m-d");

        // PropTransaction Query
        $propTransactionQuery = PropTransaction::select(DB::raw("SUM(prop_transactions.amount) AS total_amount, COUNT(distinct (prop_transactions.property_id)) AS total_hh, count(id) as total_tran"))
            ->wherein("status", [1, 2])
            ->where("tran_date", $currentDate)
            ->wherenotnull("property_id");

        if ($ulbId) {
            $propTransactionQuery->where("ulb_id", $ulbId);
        }

        $propTransactionQuery = $propTransactionQuery->get();
        $propTransactionQuery = ($propTransactionQuery
            ->map(function ($val) {
                $val->total_amount = $val->total_amount ? $val->total_amount : 0;
                return ($val);
            }))
            ->first();

        // TradeTransaction Query
        $tradeTransactionQuery = TradeTransaction::select(DB::raw("sum(paid_amount) as total_amount, count(distinct(temp_id)) as total_license, count(id) as total_tran"))
            ->wherein("status", [1, 2])
            ->where("tran_date", $currentDate);

        if ($ulbId) {
            $tradeTransactionQuery->where("ulb_id", $ulbId);
        }

        $tradeTransactionQuery = $tradeTransactionQuery->get();
        $tradeTransactionQuery = ($tradeTransactionQuery
            ->map(function ($val) {
                $val->total_amount = $val->total_amount ? $val->total_amount : 0;
                return ($val);
            }))
            ->first();

        // WaterTransaction Query
        $waterTransactionQuery = WaterTran::select(
            DB::raw("sum(amount)as total_amount , count(distinct(related_id)) as total_consumer, count(id) as total_tran")
        )
            ->wherein("status", [1, 2])
            ->where("tran_date", $currentDate)
            ->where("tran_type", 'Demand Collection');

        if ($ulbId) {
            $waterTransactionQuery->where("ulb_id", $ulbId);
        }

        $waterTransactionQuery = $waterTransactionQuery->get();
        $waterTransactionQuery = ($waterTransactionQuery
            ->map(function ($val) {
                $val->total_amount = $val->total_amount ? $val->total_amount : 0;
                return ($val);
            }))
            ->first();

        // Combine the results
        $toDayCollection = $propTransactionQuery->total_amount + $tradeTransactionQuery->total_amount + $waterTransactionQuery->total_amount;
        $data = [
            "toDayCollection" => ($toDayCollection ? $toDayCollection : 0),
            "propDetails" => $propTransactionQuery,
            "tradeDetails" => $tradeTransactionQuery,
            "waterDetails" => $waterTransactionQuery,
        ];

        return responseMsgs(true, "Mpl Report Today Coll", $data, "", 01, responseTime(), $request->getMethod(), $request->deviceId);
    } catch (\Exception $e) {
        return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $request->getMethod(), $request->deviceId);
    }
}

    // written by prity
    public function ulbList(Request $request)
    {
        try {
           $sql= "
                select id , ulb_name
                from ulb_masters
                order by ulb_name;
           ";
           $data = DB::select($sql);

            return responseMsgs(true, "Mpl Report Today Coll", $data, "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

   

    // written by prity pandey
    public function liveDashboardUpdate(Request $request)
    {
        $todayDate = Carbon::now();
        $currentFy = getFY();
        // $currentfyStartDate = $todayDate->startOfYear()->addMonths(3)->format("Y-m-d");
        // $currentfyEndDate   = $todayDate->startOfYear()->addYears(1)->addMonths(3)->addDay(-1)->format("Y-m-d");

        list($currentfyStartDate, $currentfyEndDate) = explode('-', getFY());
        $currentfyStartDate = $currentfyStartDate . "-04-01";
        $currentfyEndDate = $currentfyEndDate . "-03-31";
        $ulbId = $request->ulbId;

        $sql = "with 
                        total_props as (
                            SELECT 
                                COUNT(id) AS total_props ,ulb_id
                            FROM  prop_properties 
                            WHERE  status = 1 
                            group by ulb_id
                        ),

                        total_assessment AS (
                            SELECT 
                                COUNT(*) AS total_assessed_props,
                                ulb_id
                            FROM 
                                (
                        
                                    (
                                        SELECT  id, ulb_id 
                                        FROM prop_active_safs 
                                        WHERE  status = 1  AND application_date BETWEEN '$currentfyStartDate'  AND '$currentfyEndDate'
                                    )
                                UNION ALL
                                    (
                                        SELECT  id, ulb_id 
                                        FROM  prop_safs 
                                        WHERE  status = 1 AND application_date BETWEEN '$currentfyStartDate' AND '$currentfyEndDate'
                                    )
                        
                                UNION ALL 
                                    (
                                        SELECT  id, ulb_id 
                                        FROM  prop_rejected_safs 
                                        WHERE  status = 1  AND application_date BETWEEN '$currentfyStartDate'  AND '$currentfyEndDate'
                                    )
                        
                            ) AS a
                            WHERE ulb_id IS NOT NULL
                            GROUP BY  ulb_id
                        ) ,
                        total_occupancy_props AS (
                            SELECT  ulb_id, 
                                SUM(
                                    CASE WHEN nature = 'owned' THEN 1 ELSE 0 END
                                ) AS total_owned_props, 
                                SUM(
                                    CASE WHEN nature = 'rented' THEN 1 ELSE 0 END
                                ) AS total_rented_props, 
                                SUM(
                                    CASE WHEN nature = 'mixed' THEN 1 ELSE 0 END
                                ) AS total_mixed_owned_props 
                            FROM 
                                (
                                    SELECT 
                                        ulb_id, CASE WHEN a.cnt = a.owned THEN 'owned' WHEN a.cnt = a.rented THEN 'rented' ELSE 'mixed' END AS nature 
                                    FROM 
                                        (
                                            SELECT 
                                                ulb_id,
                                                COUNT(prop_floors.id) AS cnt, 
                                                SUM(
                                                    CASE WHEN occupancy_type_mstr_id = 1 THEN 1 ELSE 0 END
                                                ) AS owned, 
                                                SUM(
                                                    CASE WHEN occupancy_type_mstr_id = 2 THEN 1 ELSE 0 END
                                                ) AS rented 
                                            FROM 
                                                prop_floors 
                                            JOIN 
                                                prop_properties ON prop_properties.id = prop_floors.property_id
                                                AND prop_properties.prop_type_mstr_id <> 4 
                                                AND prop_properties.prop_type_mstr_id IS NOT NULL
                                            WHERE 
                                                prop_properties.status = 1 
                                            GROUP BY 
                                                property_id, ulb_id
                                        ) AS a
                                ) AS b
                            GROUP BY ulb_id
                        ) ,
                        total_vacant_land As(
                            SELECT COUNT(id) as total_vacant_land,ulb_id
                                FROM prop_properties p 
                                WHERE p.prop_type_mstr_id = 4 
                                AND status = 1 
                                group by ulb_id

                        ),
                        null_prop_data As(
                            select count(p.id) as null_prop_data,ulb_id
                                FROM prop_properties p 
                                WHERE p.prop_type_mstr_id IS NULL AND p.status=1
                                group by ulb_id
                        ),
                        null_floor_data As(
                            SELECT count(DISTINCT p.id) as null_floor_data,ulb_id
                                FROM prop_properties p 
                                LEFT JOIN prop_floors f ON f.property_id = p.id AND f.status = 1
                                WHERE p.status = 1 
                                AND p.prop_type_mstr_id IS NOT NULL 
                                AND p.prop_type_mstr_id <> 4 
                                AND f.id IS NULL
                                group by ulb_id

                        ),
                        current_payments AS (
                                                
                            SELECT ulb_id,
                                SUM(CASE WHEN UPPER(payment_mode)='CASH' THEN amount ELSE 0 END) AS current_cash_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='CHEQUE' THEN amount ELSE 0 END) AS current_cheque_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='DD' THEN amount ELSE 0 END) AS current_dd_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='CARD' THEN amount ELSE 0 END) AS current_card_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='NEFT' THEN amount ELSE 0 END) AS current_neft_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='RTGS' THEN amount ELSE 0 END) AS current_rtgs_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='ONLINE' THEN amount ELSE 0 END) AS current_Online_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='ISURE' THEN amount ELSE 0 END) AS current_isure_payment
                            FROM prop_transactions
                            WHERE tran_date BETWEEN '$currentfyStartDate' AND '$currentfyEndDate' 
                                and saf_id is null		
                                AND status = 1
                                group by ulb_id
                        ),
                        property_use_type AS(
                                            SELECT 
                                                ulb_id,
                                                SUM(CASE WHEN nature = 'residential' THEN 1 ELSE 0 END) AS total_residential_props, 
                                                SUM(CASE WHEN nature = 'commercial' THEN 1 ELSE 0 END) AS total_commercial_props,
                                                SUM(CASE WHEN nature = 'govt' THEN 1 ELSE 0 END) AS total_govt_props ,
                                                SUM(CASE WHEN nature = 'industrial' THEN 1 ELSE 0 END) AS total_industrial_props ,
                                                SUM(CASE WHEN nature = 'religious' THEN 1 ELSE 0 END) AS total_religious_props ,
                                                SUM(CASE WHEN nature = 'trust' THEN 1 ELSE 0 END) AS total_trust_props,
                                                SUM(CASE WHEN nature = 'mixed' THEN 1 ELSE 0 END) AS total_mixed_props
                                            FROM (
                                                SELECT 
                                                    ulb_id,
                                                    CASE 
                                                        WHEN cnt = residential THEN 'residential' 
                                                        WHEN cnt = commercial THEN 'commercial' 
                                                        WHEN cnt = govt THEN 'govt' 
                                                        WHEN cnt = industrial THEN 'industrial' 
                                                        WHEN cnt = religious THEN 'religious'
                                                        WHEN cnt = trust THEN 'trust'
                                                        ELSE 'mixed' 
                                                    END AS nature 
                                                FROM (
                                                    SELECT 
                                                        property_id, 
                                                        ulb_id,
                                                        COUNT(prop_floors.id) AS cnt, 
                                                        SUM(CASE WHEN usage_type_mstr_id in (1) THEN 1 ELSE 0 END) AS residential, 
                                                        SUM(CASE WHEN usage_type_mstr_id IN (13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,38,39,40,41,42) THEN 1 ELSE 0 END) AS commercial ,
                                                        SUM(CASE WHEN usage_type_mstr_id in (7,9) THEN 1 ELSE 0 END) AS govt ,
                                                        SUM(CASE WHEN usage_type_mstr_id in (33,34,35,36,37) THEN 1 ELSE 0 END) AS industrial,
                                                        SUM(CASE WHEN usage_type_mstr_id = 11 THEN 1 ELSE 0 END) AS religious,
                                                        SUM(CASE WHEN usage_type_mstr_id in (43,44,45) THEN 1 ELSE 0 END) AS trust
                                                    FROM 
                                                        prop_floors 
                                                    JOIN prop_properties ON prop_properties.id = prop_floors.property_id
                                                    WHERE 
                                                        prop_properties.status = 1 
                                                        AND prop_properties.prop_type_mstr_id <> 4 
                                                        AND prop_properties.prop_type_mstr_id IS NOT NULL
                                                    GROUP BY 
                                                        property_id, ulb_id
                                                ) AS a
                                            ) AS b
                                            GROUP BY 
                                            ulb_id
                        ),
                        zone_wise_dtd as (				
                            SELECT
                                zone_masters.id,
                                ulb_masters.id AS ulb_id,
                                CASE
                                    WHEN zone_masters.id = 1 THEN 'Zone 1'
                                    WHEN zone_masters.id = 2 THEN 'Zone 2'
                                    ELSE 'NA'
                                END AS prop_zone_name,
                                COUNT(DISTINCT prop_properties.id) AS prop_total_hh,
                                SUM(transactions.amount) AS prop_total_amount
                            FROM
                                zone_masters
                            JOIN
                                prop_properties ON prop_properties.zone_mstr_id = zone_masters.id
                            JOIN
                                (
                                    SELECT
                                        property_id,
                                        SUM(amount) AS amount,
                                        ulb_id
                                    FROM
                                        prop_transactions
                                    JOIN
                                        (
                                            SELECT DISTINCT
                                                wf_roleusermaps.user_id AS role_user_id
                                            FROM
                                                wf_roles
                                            JOIN
                                                wf_roleusermaps ON wf_roleusermaps.wf_role_id = wf_roles.id
                                                    AND wf_roleusermaps.is_suspended = FALSE
                                            JOIN
                                                wf_workflowrolemaps ON wf_workflowrolemaps.wf_role_id = wf_roleusermaps.wf_role_id
                                                    AND wf_workflowrolemaps.is_suspended = FALSE
                                            JOIN
                                                wf_workflows ON wf_workflows.id = wf_workflowrolemaps.workflow_id
                                                    AND wf_workflows.is_suspended = FALSE
                                            JOIN
                                                ulb_masters ON ulb_masters.id = wf_workflows.ulb_id
                                            WHERE
                                                wf_roles.is_suspended = FALSE
                                                AND wf_workflows.ulb_id = 2
                                                AND wf_roles.id NOT IN (8, 108)
                                                AND wf_workflows.id IN (3, 4, 5)
                                            GROUP BY
                                                wf_roleusermaps.user_id
                                            ORDER BY
                                                wf_roleusermaps.user_id
                                        ) collector ON prop_transactions.user_id = collector.role_user_id
                                    WHERE
                                        status IN (1, 2)
                                        AND UPPER(payment_mode) != 'ONLINE'
                                        AND tran_date BETWEEN '2023-04-01' AND '2024-03-31'
                                        AND property_id IS NOT NULL
                                    GROUP BY
                                        property_id, ulb_id
                                ) transactions ON transactions.property_id = prop_properties.id
                            JOIN
                                ulb_masters ON ulb_masters.id = transactions.ulb_id
                            GROUP BY
                                zone_masters.id, ulb_masters.id
                            ORDER BY
                                zone_masters.id
                        ),
                        zone_a_dtd as (
                            select *
                            from zone_wise_dtd
                            where id =1
                        ),
                        zone_b_dtd as (
                            select * 
                            from zone_wise_dtd
                            where id =2
                        ),
                        zone_dtd_collection as(
                            select ulb_masters.id as ulb_id,
                                zone_a_dtd.prop_zone_name as zone_a_name,
                                zone_a_dtd.prop_total_hh as zone_a_prop_total_hh,
                                zone_a_dtd.prop_total_amount as zone_a_prop_total_amount,
                        
                        
                                zone_b_dtd.prop_zone_name as zone_b_name,
                                zone_b_dtd.prop_total_hh as zone_b_prop_total_hh,
                                zone_b_dtd.prop_total_amount as zone_b_prop_total_amount
                        
                            from ulb_masters
                            left join zone_a_dtd on zone_a_dtd.ulb_id = ulb_masters.id
                            left join zone_b_dtd on zone_b_dtd.ulb_id = ulb_masters.id
                            order by ulb_masters.id
                        ),
                        top_wards_collections as(
                             SELECT ulb_id,(string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[1] AS top_transaction_first_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[2] AS top_transaction_sec_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[3] AS top_transaction_third_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[4] AS top_transaction_forth_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[5] AS top_transaction_fifth_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[1] AS top_transaction_first_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[2] AS top_transaction_sec_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[3] AS top_transaction_third_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[4] AS top_transaction_forth_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[5] AS top_transaction_fifth_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[1] AS top_transaction_first_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[2] AS top_transaction_sec_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[3] AS top_transaction_third_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[4] AS top_transaction_forth_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[5] AS top_transaction_fifth_ward_amt
                         
                                   FROM (
                                       SELECT 
                                                p.ulb_id,p.ward_mstr_id,
                                               SUM(t.amount) AS collected_amt,
                                               COUNT(t.id) AS collection_count,
                                               u.ward_name
                                 
                                           FROM prop_transactions t
                                           JOIN prop_properties p ON p.id=t.property_id
                                           JOIN ulb_ward_masters u ON u.id=p.ward_mstr_id
                                           WHERE t.tran_date BETWEEN '2023-04-01' AND '2024-03-31'							
                                           GROUP BY p.ward_mstr_id,u.ward_name, p.ulb_id
                                           ORDER BY collection_count DESC 
                                     
                                   ) AS top_wards_collections
                                  group by ulb_id
                         ),
                         top_area_safs As (
                                        SELECT 
                                            ulb_id,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[1] AS top_saf_first_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[2] AS top_saf_sec_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[3] AS top_saf_third_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[4] AS top_saf_forth_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[5] AS top_saf_fifth_ward_no,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[1] AS top_saf_first_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[2] AS top_saf_sec_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[3] AS top_saf_third_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[4] AS top_saf_forth_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[5] AS top_saf_fifth_ward_count
                                        FROM (
                                            SELECT 
                                                top_areas_safs.ward_mstr_id,
                                                SUM(top_areas_safs.application_count) AS application_count,
                                                u.ward_name,
                                            top_areas_safs.ulb_id
                                            FROM (
                                                SELECT 
                                                    COUNT(id) AS application_count,
                                                    ward_mstr_id,
                                                    ulb_id
                                                FROM prop_active_safs
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                            
                                                GROUP BY ward_mstr_id, ulb_id

                                                UNION ALL 

                                                SELECT 
                                                    COUNT(id) AS application_count,
                                                    ward_mstr_id,
                                                    ulb_id
                                                FROM prop_safs
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                                GROUP BY ward_mstr_id, ulb_id

                                                UNION ALL 

                                                SELECT 
                                                    COUNT(id) AS application_count,
                                                    ward_mstr_id,
                                                    ulb_id
                                                FROM prop_rejected_safs
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                            
                                                GROUP BY ward_mstr_id, ulb_id
                                            ) AS top_areas_safs
                                            JOIN ulb_ward_masters u ON u.id=top_areas_safs.ward_mstr_id
                                            GROUP BY top_areas_safs.ward_mstr_id, u.ward_name, top_areas_safs.ulb_id 
                                            ORDER BY application_count DESC 
                                        
                                        ) AS top_area_safs
                                        GROUP BY ulb_id
                        ),
                        area_wise_defaulter AS(
                            SELECT  ulb_id,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[1] AS defaulter_first_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[2] AS defaulter_sec_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[3] AS defaulter_third_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[4] AS defaulter_forth_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[5] AS defaulter_fifth_ward_no,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[1] AS defaulter_first_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[2] AS defaulter_sec_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[3] AS defaulter_third_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[4] AS defaulter_forth_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[5] AS defaulter_fifth_ward_prop_cnt,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[1] AS defaulter_first_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[2] AS defaulter_sec_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[3] AS defaulter_third_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[4] AS defaulter_forth_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[5] AS defaulter_fifth_unpaid_amount
      
                            FROM 
                                (
                                    SELECT 
                                        COUNT(a.property_id) AS defaulter_property_cnt,
                                        w.ward_name,p.ulb_id,
                                        SUM(a.unpaid_amt) AS unpaid_amount
      
                                        FROM 
                                            (
                                                SELECT
                                                     property_id,
                                                     COUNT(id) AS demand_cnt,
                                                     SUM(CASE WHEN paid_status=1 THEN 1 ELSE 0 END) AS paid_count,
                                                     SUM(CASE WHEN paid_status=0 THEN 1 ELSE 0 END) AS unpaid_count,
                                                     SUM(CASE WHEN paid_status=0 THEN balance ELSE 0 END) AS unpaid_amt
      
                                                FROM prop_demands
                                                WHERE fyear='2023-2024'								
                                                AND status=1 
                                                GROUP BY property_id
                                                ORDER BY property_id
                                        ) a 
                                        JOIN prop_properties p ON p.id=a.property_id
                                        JOIN ulb_ward_masters w ON w.id=p.ward_mstr_id
                                          
                                        WHERE a.demand_cnt=a.unpaid_count 
                                        AND p.status=1
                                          
                                        GROUP BY w.ward_name ,p.ulb_id
                                          
                                        ORDER BY defaulter_property_cnt DESC 
         
                                ) a
                                group by ulb_id
                        ),
                        
                        demand AS (
                            SELECT ulb_id,
                                SUM(
                                    CASE WHEN prop_demands.due_date BETWEEN '2023-04-01' AND '2024-03-31' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                        ELSE 0
                                        END
                                    ) AS prop_current_demand,
                                SUM(
                                    CASE WHEN prop_demands.due_date<'2023-04-01' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                        ELSE 0
                                        END
                                    ) AS prop_arrear_demand,
                                SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS prop_total_demand
                                FROM prop_demands
                                where status = 1
                                group by ulb_id
                        ),
                        collection as (	
                            SELECT prop_demands.ulb_id,
                                    SUM(
                                            CASE WHEN prop_demands.due_date BETWEEN '2023-04-01' AND '2024-03-31' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                                ELSE 0
                                                END
                                    ) AS current_collection,
                                    SUM(
                                        cASe when prop_demands.due_date <'2023-04-01' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
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
                                AND prop_transactions.tran_date  BETWEEN '2023-04-01' AND '2024-03-31'
                                --AND prop_demands.due_date<='2024-03-31'
                            GROUP BY prop_demands.ulb_id
                        ),
                        prive_collection as(
                            SELECT prop_demands.ulb_id,
                                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_prev_collection
                            FROM prop_demands
                            JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                            JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 	
                            JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 				
                            WHERE prop_demands.status =1 AND prop_tran_dtls.prop_demand_id is not null 
                                AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                                AND prop_transactions.tran_date<'2023-04-01'
                            GROUP BY prop_demands.ulb_id
                        ), 
                        dcb as(
                            select ulb_masters.id as ulb_id,
                                demand.prop_current_demand,demand.prop_arrear_demand as old_demands,
                                (Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0)) as outstanding_of_this_year,
                                collection.current_collection , collection.arrear_collection,
                            CASE 
                                WHEN SUM(COALESCE(demand.prop_current_demand, 0)) > 0 
                                THEN (SUM(COALESCE(collection.current_collection, 0)) / SUM(COALESCE(demand.prop_current_demand, 0))) * 100
                                ELSE 0
                            END AS prop_current_collection_efficiency,
                            CASE 
                                WHEN (SUM(Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0))) > 0 
                                THEN (SUM(COALESCE(collection.arrear_collection, 0)) / ((SUM(Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0)))) * 100)
                                ELSE 0
                            END AS prop_arrear_collection_efficiency
                            from ulb_masters
                            left join demand on demand.ulb_id = ulb_masters.id
                            left join collection on collection.ulb_id = ulb_masters.id
                            left join prive_collection on prive_collection.ulb_id = ulb_masters.id
                                GROUP BY 
    
                            ulb_masters.id,
                            demand.prop_current_demand,
                            demand.prop_arrear_demand,
                            (COALESCE(demand.prop_arrear_demand, 0) - COALESCE(prive_collection.total_prev_collection, 0)),
                            collection.current_collection,
                            collection.arrear_collection
                        ),
                        final_ulb_role_wise_users_count as (
                            select ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                                count(distinct (users.id)) as total_user,
                                count(case when  wf_roles.id = 1 then users.id end)as supper_admin_count,
                                count(case when  wf_roles.id = 2 then users.id end)as admin_count,
                                count(case when  wf_roles.id = 3 then users.id end)as project_manager_count,
                                count(case when  wf_roles.id = 4 then users.id end)as tl_count,
                                count(case when  wf_roles.id = 5 then users.id end)as tc_count,
                                count(case when  wf_roles.id = 6 then users.id end)as da_count,
                                count(case when  wf_roles.id = 7 then users.id end)as utc_count,
                                count(case when  wf_roles.id = 8 then users.id end)as jsk_count,
                                count(case when  wf_roles.id = 9 then users.id end)as si_count,
                                count(case when  wf_roles.id = 10 then users.id end)as eo_count,
                                count(case when  wf_roles.id = 11 then users.id end)as bo_count,
                                count(case when  wf_roles.id = 12 then users.id end)as je_count,
                                count(case when  wf_roles.id = 13 then users.id end)as sh_count,
                                count(case when  wf_roles.id = 14 then users.id end)as ae_count,
                                count(case when  wf_roles.id = 15 then users.id end)as td_count,
                                count(case when  wf_roles.id = 16 then users.id end)as ac_count,
                                count(case when  wf_roles.id = 17 then users.id end)as pmu_count,
                                count(case when  wf_roles.id = 18 then users.id end)as ach_count,
                                count(case when  wf_roles.id = 19 then users.id end)as ro_count,
                                count(case when  wf_roles.id = 20 then users.id end)as ctm_count,
                                count(case when  wf_roles.id = 21 then users.id end)as acr_count,
                                count(case when  wf_roles.id = 22 then users.id end)as cceo_count,
                                count(case when  wf_roles.id = 23 then users.id end)as mis_count,
                                count(case when  wf_roles.id = 24 then users.id end)as amo_count
                            from ulb_masters
                            left join users on users.ulb_id = ulb_masters.id and users.suspended = false
                            left join wf_roleusermaps on wf_roleusermaps.user_id = users.id and wf_roleusermaps.is_suspended = false
                            left join wf_roles on wf_roles.id = wf_roleusermaps.wf_role_id and wf_roles.is_suspended = false
                            group by ulb_masters.id,ulb_masters.ulb_name
                        ),
                        prop_demand as (
                            select distinct property_id,sum(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) as total_demand,
                                count(id) as total_demand_count,
                                count(case when paid_status =1 then id else null end) paid_demand_count,
                                count(case when paid_status !=1 then id else null end) unpaid_demand_count,
                                count(case when fyear = '2023-2024' then id else null end) as current_demand_count,
                                count(case when fyear = '2023-2024' and paid_status = 1 then id else null end) as current_demand_paid_count,
                                count(case when fyear = '2023-2024' and paid_status != 1 then id else null end) as current_demand_unpaid_count
                            from prop_demands
                            where prop_demands.status =1
                            group by property_id
                        ),
                        propertis as (
                            select prop_properties.ulb_id, count(prop_properties.id) as total_property,
                                    count(prop_demand.property_id) as total_demand_property,
                                    sum(prop_demand.total_demand) as total_demand,
                                   count (
                                           case when prop_demand.property_id is null or prop_demand.total_demand_count = prop_demand.unpaid_demand_count 
                                              then prop_properties.id 
                                               else null end
                                   ) as total_unpaid_property,
                                   count (
                                           case when prop_demand.property_id is not null and prop_demand.total_demand_count = prop_demand.paid_demand_count 
                                              then prop_properties.id 
                                               else null end
                                   ) as total_paid_property,
                                count (
                                           case when prop_demand.property_id is not null and prop_demand.current_demand_count = prop_demand.current_demand_paid_count 
                                              then prop_properties.id 
                                               else null end
                                   ) as total_current_paid_property
                            from prop_properties
                            left join prop_demand on prop_demand.property_id = prop_properties.id	
                            where prop_properties.status =1
                            group by prop_properties.ulb_id
                        ),
                        prop_tran as(
                        select ulb_masters.id as ulb_id,ulb_masters.ulb_name,
                            propertis.total_property,propertis.total_demand_property, propertis.total_demand, propertis.total_unpaid_property,
                            propertis.total_paid_property, propertis.total_current_paid_property
                        from ulb_masters
                        left join propertis on propertis.ulb_id = ulb_masters.id
                        )                              
                        
                        select ulb_masters.id,ulb_masters.ulb_name,
                            total_props.*,
                            total_assessment.*,
                            total_occupancy_props.*,
                            current_payments.*,
                            total_vacant_land.*,
                            null_prop_data.*,
                            null_floor_data.*,
                            demand.*,
                            property_use_type.*,
                            zone_dtd_collection.*,
                            top_wards_collections.*,
                            top_area_safs.*,
                            area_wise_defaulter.*,
                            dcb.*,
                            final_ulb_role_wise_users_count.*,
                            prop_tran.*
                        from ulb_masters
                        left join total_props on total_props.ulb_id = ulb_masters.id
                        left join total_assessment on total_assessment.ulb_id = ulb_masters.id
                        left join total_occupancy_props on total_occupancy_props.ulb_id = ulb_masters.id
                        left join current_payments on current_payments.ulb_id = ulb_masters.id
                        left join total_vacant_land on total_vacant_land.ulb_id = ulb_masters.id
                        left join null_prop_data on null_prop_data.ulb_id = ulb_masters.id
                        left join null_floor_data on null_floor_data.ulb_id = ulb_masters.id       
                        left join demand on demand.ulb_id = ulb_masters.id       
                        left join property_use_type on property_use_type.ulb_id = ulb_masters.id 
                        left join zone_dtd_collection on zone_dtd_collection.ulb_id = ulb_masters.id 
                        left join top_wards_collections on top_wards_collections.ulb_id = ulb_masters.id 
                        left join top_area_safs on top_area_safs.ulb_id = ulb_masters.id 
                        left join area_wise_defaulter on area_wise_defaulter.ulb_id = ulb_masters.id 
                        left join dcb on dcb.ulb_id = ulb_masters.id
                        left join final_ulb_role_wise_users_count on final_ulb_role_wise_users_count.ulb_id = ulb_masters.id
                        left join prop_tran on prop_tran.ulb_id = ulb_masters.id




                        --where ulb_masters.id =2
        ";
       // print_var($sql);die;
        $data = DB::select($sql);
        //dd($data);
        //dd($waterdata);
        $tradedata = $this->tradedetails();
        $waterdata = $this->waterdetails();
        DB::connection("pgsql_reports")->beginTransaction();
        //return $data = $data[0];
       //dd(($waterdata->where("ulb_id","1")->first())->ulb_id);
    //    $tradedata = $this->tradedetails();
    //    $waterdata = $this->waterdetails();
        foreach( $data as $key=>$val)
        {
            $mMplYearlyReport = new MplYearlyReport();

            $updateReqs = [
                "ulb_name"=>$val->ulb_name??"",
                "total_assessment" => $val->total_assessed_props??0,
                "total_property" => $val->total_props??0,
                "owned_property" => $val->total_owned_props??0,
                "rented_property" => $val->total_rented_props??0,
                "mixed_property" => $val->total_mixed_owned_props??0,
                "vacant_property" => ($val->total_vacant_land + $val->null_prop_data +$val->null_floor_data)??0,    
                "current_year_cash_collection" => $val->current_cash_payment??0,
                "current_year_card_collection" => $val->current_card_payment??0 ,
                "current_year_dd_collection"   => $val->current_dd_payment??0,
                "current_year_cheque_collection" => $val->current_cheque_payment??0,
                "current_year_neft_collection" => $val->current_neft_payment??0,
                "current_year_rtgs_collection" => $val->current_rtgs_payment??0,
                "current_year_upi_collection" => $val->current_isure_payment??0,
                "current_year_online_collection" => $val->current_online_payment ??0,
                'prop_current_demand'  => $val->prop_current_demand??0,
                'prop_arrear_demand'  => $val->old_demands??0,
                'prop_outsatnding_current_demand'  => $val->outstanding_of_this_year??0,
                'prop_current_collection'  => $val->current_collection??0,
                'prop_arrear_collection'  => $val->arrear_collection??0,
                'prop_current_collection_efficiency'  => $val->prop_current_collection_efficiency??0,
                'prop_arrear_collection_efficiency'  => $val->prop_arrear_collection_efficiency??0,

                

                'total_residential_props'  => $val->total_residential_props??0,
                'total_commercial_props'  => $val->total_commercial_props??0,
                'total_govt_props'  => $val->total_govt_props??0,
                'total_industrial_props'  => $val->total_industrial_props??0,
                'total_religious_props'  => $val->total_religious_props??0,
                'total_trust_props'  => $val->total_trust_props??0,
                'total_mixed_props'  => $val->total_mixed_props??0,
                'a_zone_name' => ($val->zone_a_name) ?? "",
                'a_prop_total_hh' => ($val->zone_a_prop_total_hh) ?? 0,
                'a_prop_total_amount' => ($val->zone_a_prop_total_amount) ?? 0,
                'b_zone_name' => ($val->zone_b_name) ?? "",
                'b_prop_total_hh' => ($val->zone_b_prop_total_hh) ?? 0,
                'b_prop_total_amount' => ($val->zone_b_prop_total_amount) ?? 0,

                # Top Areas Property Transactions
                "top_area_property_transaction_ward1_name" => $val->top_transaction_first_ward_no,
                "top_area_property_transaction_ward2_name" => $val->top_transaction_sec_ward_no,
                "top_area_property_transaction_ward3_name" => $val->top_transaction_third_ward_no,
                "top_area_property_transaction_ward4_name" => $val->top_transaction_forth_ward_no,
                "top_area_property_transaction_ward5_name" => $val->top_transaction_fifth_ward_no,
                "top_area_property_transaction_ward1_count" => $val->top_transaction_first_ward_count,
                "top_area_property_transaction_ward2_count" => $val->top_transaction_sec_ward_count,
                "top_area_property_transaction_ward3_count" => $val->top_transaction_third_ward_count,
                "top_area_property_transaction_ward4_count" => $val->top_transaction_forth_ward_count,
                "top_area_property_transaction_ward5_count" => $val->top_transaction_fifth_ward_count,

                #Top Area Safs
                "top_area_saf_ward1_name" => $val->top_saf_first_ward_no,
                "top_area_saf_ward2_name" => $val->top_saf_sec_ward_no,
                "top_area_saf_ward3_name" => $val->top_saf_third_ward_no,
                "top_area_saf_ward4_name" => $val->top_saf_forth_ward_no,
                "top_area_saf_ward5_name" => $val->top_saf_fifth_ward_no,
                "top_area_saf_ward1_count" => $val->top_saf_first_ward_count,
                "top_area_saf_ward2_count" => $val->top_saf_sec_ward_count,
                "top_area_saf_ward3_count" => $val->top_saf_third_ward_count,
                "top_area_saf_ward4_count" => $val->top_saf_forth_ward_count,
                "top_area_saf_ward5_count" => $val->top_saf_fifth_ward_count,

                #Top Area defaulters
                "top_defaulter_ward1_name" => $val->defaulter_first_ward_no,
                "top_defaulter_ward2_name" => $val->defaulter_sec_ward_no,
                "top_defaulter_ward3_name" => $val->defaulter_third_ward_no,
                "top_defaulter_ward4_name" => $val->defaulter_forth_ward_no,
                "top_defaulter_ward5_name" => $val->defaulter_fifth_ward_no,
                "top_defaulter_ward1_count" => $val->defaulter_first_ward_prop_cnt,
                "top_defaulter_ward2_count" => $val->defaulter_sec_ward_prop_cnt,
                "top_defaulter_ward3_count" => $val->defaulter_third_ward_prop_cnt,
                "top_defaulter_ward4_count" => $val->defaulter_forth_ward_prop_cnt,
                "top_defaulter_ward5_count" => $val->defaulter_fifth_ward_prop_cnt,
                "top_defaulter_ward1_amount" => $val->defaulter_first_unpaid_amount,
                "top_defaulter_ward2_amount" => $val->defaulter_sec_unpaid_amount,
                "top_defaulter_ward3_amount" => $val->defaulter_third_unpaid_amount,
                "top_defaulter_ward4_amount" => $val->defaulter_forth_unpaid_amount,
                "top_defaulter_ward5_amount" => $val->defaulter_fifth_unpaid_amount,

                #Employee Count
                "tc_count" => $val->tc_count,
                "da_count" => $val->da_count,
                "si_count" => $val->si_count,
                "eo_count" => $val->eo_count,
                "utc_count" => $val->utc_count,
                "bo_count" => $val->bo_count,

                #Payment status against property
                "total_unpaid_property" => $val->total_unpaid_property,
                "total_paid_property" => $val->total_paid_property,
                "current_unpaid_property" => $val->current_unpaid_property,
                
             #trade
            'total_trade_licenses'  => ($tradedata->where("ulb_id",$val->id)->first())->total_trade_licenses ?? 0,
            'total_trade_licenses_underprocess' => ($tradedata->where("ulb_id",$val->id)->first())->total_trade_licenses_underprocess ?? 0,
            'trade_current_cash_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_cash_payment ?? 0,
            'trade_current_cheque_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_cheque_payment ?? 0,
            'trade_current_dd_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_dd_payment ?? 0,
            'trade_current_card_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_card_payment ?? 0,
            'trade_current_neft_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_neft_payment ?? 0,
            'trade_current_rtgs_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_rtgs_payment ?? 0,
            'trade_current_online_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_online_payment ?? 0,
            'trade_current_online_counts' => ($tradedata->where("ulb_id",$val->id)->first())->trade_current_online_counts ?? 0,
            'trade_lastyear_cash_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_cash_payment ?? 0,
            'trade_lastyear_cheque_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_cheque_payment ?? 0,
            'trade_lastyear_dd_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_dd_payment ?? 0,
            'trade_lastyear_neft_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_neft_payment ?? 0,
            'trade_lastyear_rtgs_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_rtgs_payment ?? 0,
            'trade_lastyear_online_payment' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_online_payment ?? 0,
            'trade_lastyear_online_counts' => ($tradedata->where("ulb_id",$val->id)->first())->trade_lastyear_online_counts ?? 0,
            'trade_renewal_less_then_1_year' => ($tradedata->where("ulb_id",$val->id)->first())->less_then_1_year ?? 0,
            'trade_renewal_more_then_1_year' => ($tradedata->where("ulb_id",$val->id)->first())->more_then_1_year ?? 0,
            'trade_renewal_more_then_1_year_and_less_then_5_years' => ($tradedata->where("ulb_id",$val->id)->first())->more_then_1_year_and_less_then_5_years ?? 0,
            'trade_renewal_more_then_5_year' => ($tradedata->where("ulb_id",$val->id)->first())->more_then_5_year ?? 0,

             #water
             'water_connection_underprocess'  => ($waterdata->where("ulb_id",$val->id)->first())->water_connection_underprocess,
             'water_fix_connection_type'  => ($waterdata->where("ulb_id",$val->id)->first())->water_fix_connection_type,
             'water_meter_connection_type'  => ($waterdata->where("ulb_id",$val->id)->first())->water_meter_connection_type,
             'water_current_demand'  => ($waterdata->where("ulb_id",$val->id)->first())->water_current_demand,
             'water_arrear_demand'  => ($waterdata->where("ulb_id",$val->id)->first())->water_arrear_demand,
             'water_current_collection'  => ($waterdata->where("ulb_id",$val->id)->first())->water_current_collection,
             'water_arrear_collection'  => ($waterdata->where("ulb_id",$val->id)->first())->water_arrear_collection,
             'water_total_collection'  => ($waterdata->where("ulb_id",$val->id)->first())->water_total_collection,
             'water_total_prev_collection'  => ($waterdata->where("ulb_id",$val->id)->first())->water_total_prev_collection,
             'water_arrear_collection_efficiency'  => ($waterdata->where("ulb_id",$val->id)->first())->water_arrear_collection_efficiency,
             'water_current_collection_efficiency'  => ($waterdata->where("ulb_id",$val->id)->first())->water_current_collection_efficiency,
             'water_current_outstanding'  => ($waterdata->where("ulb_id",$val->id)->first())->water_current_outstanding,
             'water_arrear_outstanding'  => ($waterdata->where("ulb_id",$val->id)->first())->water_arrear_outstanding

            ];
            $testData = $mMplYearlyReport->where("ulb_id",$val->id)->where("fyear",$currentFy)->first();
            if($testData)
            {
                $mMplYearlyReport->where("ulb_id",$val->id)->where("fyear",$currentFy)->update($updateReqs);
                print_var("Update =>".$val->id." ".$val->ulb_name);
                continue;
            }
            $updateReqs["fyear"] =$currentFy;
            $updateReqs["ulb_id"]=$val->id;
            $updateReqs["created_at"]=Carbon::now();
            $mMplYearlyReport->create($updateReqs);
            print_var("insert =>".$val->id." ".$val->ulb_name);
        }
        
        DB::connection("pgsql_reports")->commit();return ("ok");
        $currentFy = getFY();

        $tradedata = $this->tradedetails();
        // $propdata = $this->propertydetails();
        // $waterdata = $this->waterdetails();

        // $updateReqs = [
        //     "total_assessment" => $data->total_assessed_props,
        //     "total_prop_vacand" => $propdata->total_vacant_land + $propdata->null_prop_data + $propdata->null_floor_data,            "total_prop_residential" => $data->total_residential_props,
        //     "total_prop_commercial"  => $data->total_commercial_props,
        //     "total_prop_industrial" => $data->total_industrial_props,
        //     "total_prop_gbsaf" => $data->total_govt_props,
        //     "total_prop_mixe" => $data->total_mixed_commercial_props,
        //     "total_prop_religious" => $data->total_religious_props,
        //     "total_property" => $data->total_props,
        //     "vacant_property" => $propdata->total_vacant_land + $propdata->null_prop_data + $propdata->null_floor_data,
        //     "owned_property" => $data->total_owned_props,
        //     "rented_property" => $data->total_rented_props,
        //     "mixed_property" => $data->total_mixed_owned_props,

        //     "current_year_cash_collection" => $data->current_cash_payment,
        //     "current_year_card_collection" => $data->current_card_payment + $data->current_card_wise_payment,
        //     "current_year_dd_collection"   => $data->current_dd_payment,
        //     "current_year_cheque_collection" => $data->current_cheque_payment,
        //     "current_year_neft_collection" => $data->current_neft_payment,
        //     "current_year_rtgs_collection" => $data->current_rtgs_payment,
        //     "current_year_upi_collection" => $data->current_qr_payment,
        //     "current_year_online_collection" => $data->current_online_payment,


        //     // "count_not_paid_3yrs" => $data->pending_cnt_3yrs,
        //     // "amount_not_paid_3yrs" => $data->amt_not_paid_3yrs,
        //     // "count_not_paid_2yrs" => $data->pending_cnt_2yrs,
        //     // "amount_not_paid_2yrs" => $data->amt_not_paid_2yrs,
        //     // "count_not_paid_1yrs" => $data->pending_cnt_1yrs,
        //     // "amount_not_paid_1yrs" => $data->amt_not_paid_1yrs,
        //     // "demand_outstanding_this_year" => $data->outstanding_amt_curryear,
        //     // "demand_outstanding_from_this_year_prop_count" => $data->outstanding_cnt_curryear,
        //     // "demand_outstanding_coll_this_year" => $data->recoverable_demand_currentyr,

        //     "last_year_payment_amount" => $data->lastyr_pmt_amt,
        //     "last_year_payment_count" => $data->lastyr_pmt_cnt,
        //     "this_year_payment_count" => $data->currentyr_pmt_cnt,
        //     "this_year_payment_amount" => $data->currentyr_pmt_amt,
        //     "collection_against_current_demand" => $data->current_demand_collection,
        //     "collection_againt_arrear_demand" => $data->arrear_demand_collection,
        //     // "mutation_this_year_count" => $data->current_yr_mutation_count,
        //     // "assessed_property_this_year_achievement" => $data->outstanding_amt_lastyear,
        //     // "assessed_property_this_year_achievement" => $data->outstanding_cnt_lastyear,
        //     // "assessed_property_this_year_achievement" => $data->recoverable_demand_lastyear,

        //     // "assessed_property_this_year_achievement" => $data->last_yr_mutation_count,

        //     // "assessed_property_this_year_achievement" => $data->top_transaction_first_ward_no,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_sec_ward_no,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_third_ward_no,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_forth_ward_no,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_fifth_ward_no,
        //     "top_area_property_transaction_ward1_count" => $data->top_transaction_first_ward_count,
        //     "top_area_property_transaction_ward2_count" => $data->top_transaction_sec_ward_count,
        //     "top_area_property_transaction_ward3_count" => $data->top_transaction_third_ward_count,
        //     "top_area_property_transaction_ward4_count" => $data->top_transaction_forth_ward_count,
        //     "top_area_property_transaction_ward5_count" => $data->top_transaction_fifth_ward_count,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_first_ward_amt,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_sec_ward_amt,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_third_ward_amt,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_forth_ward_amt,
        //     // "assessed_property_this_year_achievement" => $data->top_transaction_fifth_ward_amt,

        //     "top_area_saf_ward1_name" => $data->top_saf_first_ward_no,
        //     "top_area_saf_ward2_name" => $data->top_saf_sec_ward_no,
        //     "top_area_saf_ward3_name" => $data->top_saf_third_ward_no,
        //     "top_area_saf_ward4_name" => $data->top_saf_forth_ward_no,
        //     "top_area_saf_ward5_name" => $data->top_saf_fifth_ward_no,
        //     "top_area_saf_ward1_count" => $data->top_saf_first_ward_count,
        //     "top_area_saf_ward2_count" => $data->top_saf_sec_ward_count,
        //     "top_area_saf_ward3_count" => $data->top_saf_third_ward_count,
        //     "top_area_saf_ward4_count" => $data->top_saf_forth_ward_count,
        //     "top_area_saf_ward5_count" => $data->top_saf_fifth_ward_count,

        //     "top_defaulter_ward1_name" => $data->defaulter_first_ward_no,
        //     "top_defaulter_ward2_name" => $data->defaulter_sec_ward_no,
        //     "top_defaulter_ward3_name" => $data->defaulter_third_ward_no,
        //     "top_defaulter_ward4_name" => $data->defaulter_forth_ward_no,
        //     "top_defaulter_ward5_name" => $data->defaulter_fifth_ward_no,
        //     "top_defaulter_ward1_count" => $data->defaulter_first_ward_prop_cnt,
        //     "top_defaulter_ward2_count" => $data->defaulter_sec_ward_prop_cnt,
        //     "top_defaulter_ward3_count" => $data->defaulter_third_ward_prop_cnt,
        //     "top_defaulter_ward4_count" => $data->defaulter_forth_ward_prop_cnt,
        //     "top_defaulter_ward5_count" => $data->defaulter_fifth_ward_prop_cnt,
        //     "top_defaulter_ward1_amount" => $data->defaulter_first_unpaid_amount,
        //     "top_defaulter_ward2_amount" => $data->defaulter_sec_unpaid_amount,
        //     "top_defaulter_ward3_amount" => $data->defaulter_third_unpaid_amount,
        //     "top_defaulter_ward4_amount" => $data->defaulter_forth_unpaid_amount,
        //     "top_defaulter_ward5_amount" => $data->defaulter_fifth_unpaid_amount,

        //     // "total_assessed_residential" => $data->applied_res_safs,
        //     // "total_assessed_commercial" => $data->applied_comm_safs,
        //     // "total_assessed_industrial" => $data->applied_industries_safs,
        //     // "total_assessed_gbsaf" => $data->applied_gb_safs,
        //     // "assessed_property_this_year_achievement" => $data->current_online_payment,
        //     // "assessed_property_this_year_achievement" => $data->current_online_counts,
        //     // "assessed_property_this_year_achievement" => $data->prev_year_jskcollection,
        //     // "assessed_property_this_year_achievement" => $data->prev_year_jskcount,
        //     // "assessed_property_this_year_achievement" => $data->current_year_jskcollection,
        //     // "assessed_property_this_year_achievement" => $data->current_year_jskcount,
        //     // "assessed_property_this_year_achievement" => $data->lastyear_cash_payment,
        //     // "assessed_property_this_year_achievement" => $data->lastyear_cheque_payment,
        //     // "assessed_property_this_year_achievement" => $data->lastyear_dd_payment,
        //     // "assessed_property_this_year_achievement" => $data->lastyear_neft_payment,
        //     // "assessed_property_this_year_achievement" => $data->lastyear_online_payment,
        //     // "date" => $todayDate,
        //     // "fyear" => "$currentFy",
        //     // "ulb_id" => "2",
        //     // "ulb_name" => "Akola Municipal Corporation",


        
        //     // #property_new
        //     'a_zone_name'  => $propdata->a_zone_name,
        //     'a_prop_total_hh'  => $propdata->a_prop_total_hh,
        //     'a_prop_total_amount'  => $propdata->a_prop_total_amount,
        //     'b_zone_name'  => $propdata->b_zone_name,
        //     'b_prop_total_hh'  => $propdata->b_prop_total_hh,
        //     'b_prop_total_amount'  => $propdata->b_prop_total_amount,
        //     'c_zone_name'  => $propdata->c_zone_name,
        //     'c_prop_total_hh'  => $propdata->c_prop_total_hh,
        //     'c_prop_total_amount'  => $propdata->c_prop_total_amount,
        //     'd_zone_name'  => $propdata->d_zone_name,
        //     'd_prop_total_hh'  => $propdata->d_prop_total_hh,
        //     'd_prop_total_amount'  => $propdata->d_prop_total_amount,
        //     'prop_current_demand'  => $propdata->prop_current_demand,
        //     'prop_arrear_demand'  => $propdata->prop_arrear_demand,
        //     'prop_total_demand'  => $propdata->prop_total_demand,
        //     //'prop_current_collection'  => $propdata->prop_current_collection,
        //     //'prop_arrear_collection'  => $propdata->prop_arrear_collection,
        //     'prop_total_collection'  => $propdata->prop_total_collection,





        //     #water
        //     'water_connection_underprocess'  => $waterdata->water_connection_underprocess,
        //     //'water_total_consumer'  => $waterdata->water_total_consumer,
        //     'water_fix_connection_type'  => $waterdata->water_fix_connection_type,
        //     'water_meter_connection_type'  => $waterdata->water_meter_connection_type,
        //     'water_current_demand'  => $waterdata->water_current_demand,
        //     'water_arrear_demand'  => $waterdata->water_arrear_demand,
        //     //'water_total_demand'  => $waterdata->water_total_demand,
        //     'water_current_collection'  => $waterdata->water_current_collection,
        //     'water_arrear_collection'  => $waterdata->water_arrear_collection,
        //     'water_total_collection'  => $waterdata->water_total_collection,
        //     'water_total_prev_collection'  => $waterdata->water_total_prev_collection,
        //     'water_arrear_collection_efficiency'  => $waterdata->water_arrear_collection_efficiency,
        //     'water_current_collection_efficiency'  => $waterdata->water_current_collection_efficiency,
        //     'water_current_outstanding'  => $waterdata->water_current_outstanding,
        //     'water_arrear_outstanding'  => $waterdata->water_arrear_outstanding,
        // ];

        // $mMplYearlyReport->where('fyear', $currentFy)
        //     ->update($updateReqs);

        // // $updateReqs->push(["fyear" => "$currentFy"]);
        // // $mMplYearlyReport->create($updateReqs);

        // dd("ok");
    }
    public function tradedetails()
    {
        $sql = "
        with total_trade as(
                                    
            select count(id) as total_trade_licenses,ulb_id
            from trade_licences
            where is_active = true 
            group by ulb_id
        ),
               
        active_trade_license as(
                                            
            select count(id) as total_trade_licenses_underprocess,ulb_id
            FROM active_trade_licences
            WHERE is_active = true
            group by ulb_id
        ),
               
          
        payment_mode as (
            select distinct (UPPER(payment_mode)) as payment_mode,ulb_id
            from trade_transactions
        
        ),
        current_payments AS (
            SELECT payment_mode.ulb_id,                                                
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='CASH' THEN  paid_amount ELSE 0 END) AS trade_current_cash_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='CHEQUE' THEN  paid_amount ELSE 0 END) AS trade_current_cheque_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='DD' THEN  paid_amount ELSE 0 END) AS trade_current_dd_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='CARD PAYMENT' THEN  paid_amount ELSE 0 END) AS trade_current_card_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='NEFT' THEN  paid_amount ELSE 0 END) AS trade_current_neft_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='RTGS' THEN  paid_amount ELSE 0 END) AS trade_current_rtgs_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='ONLINE' THEN  paid_amount ELSE 0 END) AS trade_current_online_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='ONLINE' THEN 1 ELSE 0 END) AS trade_current_online_counts,
        
                        0 AS trade_lastyear_cash_payment,
                        0 AS trade_lastyear_cheque_payment,
                        0 AS trade_lastyear_dd_payment,
                        0 AS trade_lastyear_neft_payment,
                        0 AS trade_lastyear_rtgs_payment,
                        0 AS trade_lastyear_online_payment,
                        Null::numeric AS trade_lastyear_online_counts
            FROM payment_mode
            join trade_transactions on UPPER (trade_transactions.payment_mode) = payment_mode.payment_mode
            WHERE tran_date BETWEEN '2023-04-01' AND '2024-03-31'				
                AND  status=1 
            group by payment_mode.ulb_id
        
        ),
        lastyear_payments AS (
                SELECT payment_mode.ulb_id,                                        
                        0 AS trade_current_cash_payment,
                        0 AS trade_current_cheque_payment,
                        0 AS trade_current_dd_payment,
                        0  AS trade_current_card_payment,
                        0 AS trade_current_neft_payment,
                        0 AS trade_current_rtgs_payment,
                        0 AS trade_current_online_payment,
                        Null::numeric AS trade_current_online_counts,
        
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='CASH' THEN  paid_amount ELSE 0 END) AS trade_lastyear_cash_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='CHEQUE' THEN  paid_amount ELSE 0 END) AS trade_lastyear_cheque_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='DD' THEN  paid_amount ELSE 0 END) AS trade_lastyear_dd_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='NEFT' THEN  paid_amount ELSE 0 END) AS trade_lastyear_neft_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='RTGS' THEN  paid_amount ELSE 0 END) AS trade_lastyear_rtgs_payment,
        
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='ONLINE' THEN  paid_amount ELSE 0 END) AS trade_lastyear_online_payment,
                        SUM(CASE WHEN UPPER(payment_mode.payment_mode)='ONLINE' THEN 1 ELSE 0 END) AS trade_lastyear_online_counts
        
            FROM payment_mode
            join trade_transactions on UPPER (trade_transactions.payment_mode) = payment_mode.payment_mode
            WHERE tran_date BETWEEN '2022-04-01' AND '2023-03-31'					
                AND  status=1 
            group by payment_mode.ulb_id
        
        ),
        payment as (
                    select 
                    ulb_id,                                   
                    sum (trade_current_cash_payment) as trade_current_cash_payment, 
                    sum (trade_current_cheque_payment) as trade_current_cheque_payment, 
                    sum(trade_current_dd_payment) as trade_current_dd_payment,
                    sum(trade_current_card_payment) as trade_current_card_payment,
                    sum(trade_current_neft_payment) as trade_current_neft_payment,
                    sum(trade_current_rtgs_payment) as trade_current_rtgs_payment,
                    sum(trade_current_online_payment) as trade_current_online_payment,
                    sum(trade_current_online_counts) as trade_current_online_counts,
                    sum (trade_lastyear_cash_payment) as trade_lastyear_cash_payment, 
                    sum(trade_lastyear_cheque_payment) as trade_lastyear_cheque_payment,
                    sum(trade_lastyear_dd_payment) as trade_lastyear_dd_payment,
                    sum(trade_lastyear_neft_payment) as trade_lastyear_neft_payment,
                    sum(trade_lastyear_rtgs_payment) as trade_lastyear_rtgs_payment,
                    sum(trade_lastyear_online_payment) as trade_lastyear_online_payment,
                    sum(trade_lastyear_online_counts) as trade_lastyear_online_counts
        
                from (
                    (
                    select * 
                    from current_payments
                    )
                    union all
                    (
                    select * 
                    from lastyear_payments
                    )
                )as payment
                group by ulb_id
               ),
        
              
        renewal_pending_trade as(   
                select 
                ulb_id,
                count(
                    case when ( 
                                (DATE_PART('YEAR', current_date :: DATE) - DATE_PART('YEAR', valid_upto :: DATE)) * 12
                                +(DATE_PART('Month', current_date :: DATE) - DATE_PART('Month', valid_upto :: DATE))
                                )/12 <= 1 then id else null end
                                ) as less_then_1_year,
                count(
                    case when ( 
                                (DATE_PART('YEAR', current_date :: DATE) - DATE_PART('YEAR', valid_upto :: DATE)) * 12
                                +(DATE_PART('Month', current_date :: DATE) - DATE_PART('Month', valid_upto :: DATE))
                                )/12 > 1 then id else null end
                                ) as more_then_1_year, 
                count(
                    case when ( 
                                (DATE_PART('YEAR', current_date :: DATE) - DATE_PART('YEAR', valid_upto :: DATE)) * 12
                                +(DATE_PART('Month', current_date :: DATE) - DATE_PART('Month', valid_upto :: DATE))
                                )/12 > 1 and 
                                ( 
                                (DATE_PART('YEAR', current_date :: DATE) - DATE_PART('YEAR', valid_upto :: DATE)) * 12
                                +(DATE_PART('Month', current_date :: DATE) - DATE_PART('Month', valid_upto :: DATE))
                                )/12 <=5 then id else null end
                                ) as more_then_1_year_and_less_then_5_years,
                count(
                    case when ( 
                                (DATE_PART('YEAR', current_date :: DATE) - DATE_PART('YEAR', valid_upto :: DATE)) * 12
                                +(DATE_PART('Month', current_date :: DATE) - DATE_PART('Month', valid_upto :: DATE))
                                )/12 >5 then id else null end
                                ) as more_then_5_year
            from trade_licences
            where is_active = true and valid_upto<current_date
            group by ulb_id
                )
        select ulb_masters.id as ulb_id,ulb_name,total_trade.*,active_trade_license.*,payment.*,renewal_pending_trade.*
        from ulb_masters
        left join total_trade on ulb_masters.id = total_trade.ulb_id
        left join active_trade_license on ulb_masters.id = active_trade_license.ulb_id
        left join payment on ulb_masters.id = payment.ulb_id
        left join renewal_pending_trade on ulb_masters.id = renewal_pending_trade.ulb_id
                    
        ";
        $respons = collect(DB::connection("pgsql_trade")->select($sql));
        return (object)$respons;
    }

    public function propertydetails()
    {
        $todayDate = Carbon::now();
        $currentFy = getFY();
        list($currentfyStartDate, $currentfyEndDate) = explode('-', getFY());
        $currentfyStartDate = $currentfyStartDate . "-04-01";
        $currentfyEndDate = $currentfyEndDate . "-03-31";
        #license_underprocess
        $sql_property_zonal = "
                                    
                                    select zone_masters.id,
                                    case when zone_masters.id =1 then 'A-East Zone'
                                        when zone_masters.id = 2 then 'B-West Zone'
                                        when zone_masters.id = 3 then 'C-North Zone'
                                        when zone_masters.id = 4 then 'D-South Zone'
                                        else 'NA' end as prop_zone_name, 
                                    count(distinct(prop_properties.id)) as prop_total_hh, sum(amount) as prop_total_amount
                                    from zone_masters
                                    join prop_properties on  prop_properties.zone_mstr_id =zone_masters.id 
                                    join (
                                        select property_id,sum(amount) as amount
                                        from prop_transactions
                                        JOIN (
                                                                            
                                                SELECT DISTINCT wf_roleusermaps.user_id as role_user_id
                                                FROM wf_roles
                                                JOIN wf_roleusermaps ON wf_roleusermaps.wf_role_id = wf_roles.id 
                                                    AND wf_roleusermaps.is_suspended = FALSE
                                                JOIN wf_workflowrolemaps ON wf_workflowrolemaps.wf_role_id = wf_roleusermaps.wf_role_id
                                                    AND wf_workflowrolemaps.is_suspended = FALSE
                                                JOIN wf_workflows ON wf_workflows.id = wf_workflowrolemaps.workflow_id AND wf_workflows.is_suspended = FALSE 
                                                JOIN ulb_masters ON ulb_masters.id = wf_workflows.ulb_id
                                                WHERE wf_roles.is_suspended = FALSE 
                                                    AND wf_workflows.ulb_id = 2
                                                    AND wf_roles.id not in (8,108)
                                                    --AND wf_workflows.id in (3,4,5)
                                                GROUP BY wf_roleusermaps.user_id
                                                ORDER BY wf_roleusermaps.user_id
                                         ) collecter on prop_transactions.user_id  = collecter.role_user_id
                                        where status in (1,2)
                                        and UPPER (payment_mode) != 'ONLINE'
                                        and tran_date between '$currentfyStartDate' and '$currentfyEndDate'
                                        and property_id is not null
                                        group by property_id
                                    ) transactions on transactions.property_id = prop_properties.id
                                    group by zone_masters.id 
                                    order by zone_masters.id 
        ";
        # total demands
        $sql_property_demand = "
                select 
                SUM(
                    CASE WHEN prop_demands.fyear  = '$currentFy' then prop_demands.total_tax
                        ELSE 0
                        END
                ) AS prop_current_demand,
                SUM(
                    CASE WHEN prop_demands.fyear < '$currentFy' then prop_demands.total_tax
                        ELSE 0
                        END
                ) AS prop_arrear_demand,
                SUM(prop_demands.total_tax) AS prop_total_demand
            FROM prop_demands
            WHERE prop_demands.status =1 
            ";
        # total collection
        $sql_property_collection = "
                select sum(amount) AS prop_total_collection,ulb_id
                from prop_transactions
                where prop_transactions.tran_date between '$currentfyStartDate' and '$currentfyEndDate' 
                and saf_id is  null
                and prop_transactions.status = 1
                group by ulb_id
             ";
        $sql_prop_vacant_land = "
                         SELECT 
                             (
                                 SELECT COUNT(id) 
                                 FROM prop_properties p 
                                 WHERE p.prop_type_mstr_id = 4 
                                 AND status = 1 
                                 AND ulb_id = 2
                             ) AS total_vacant_land
         ";
        $sql_prop_null_data = "
                           SELECT 
                           (
                           select count(p.id) as null_prop_data
                               FROM prop_properties p 
                               WHERE p.prop_type_mstr_id IS NULL AND p.status=1
                       ) AS null_prop_data";
        $sql_prop_null_floor_data = "
                       SELECT count(DISTINCT p.id) as null_floor_data
                       FROM prop_properties p 
                       LEFT JOIN prop_floors f ON f.property_id = p.id AND f.status = 1
                       WHERE p.status = 1 
                       AND p.prop_type_mstr_id IS NOT NULL 
                       AND p.prop_type_mstr_id <> 4 
                       AND f.id IS NULL
                   ";
        $respons = [];
        $data = collect(DB::connection("pgsql")->select($sql_property_zonal));

        $respons["a_zone_name"] = (collect($data)->where("id", 1)->first()->prop_zone_name) ?? 0;
        $respons["a_prop_total_hh"] = (collect($data)->where("id", 1)->first()->prop_total_hh) ?? 0;
        $respons["a_prop_total_amount"] = (collect($data)->where("id", 1)->first()->prop_total_amount) ?? 0;

        $respons["b_zone_name"] = (collect($data)->where("id", 2)->first()->prop_zone_name) ?? 0;
        $respons["b_prop_total_hh"] = (collect($data)->where("id", 2)->first()->prop_total_hh) ?? 0;
        $respons["b_prop_total_amount"] = (collect($data)->where("id", 2)->first()->prop_total_amount) ?? 0;

        $respons["c_zone_name"] = (collect($data)->where("id", 3)->first()->prop_zone_name) ?? 0;
        $respons["c_prop_total_hh"] = (collect($data)->where("id", 3)->first()->prop_total_hh) ?? 0;
        $respons["c_prop_total_amount"] = (collect($data)->where("id", 3)->first()->prop_total_amount) ?? 0;

        $respons["d_zone_name"] = (collect($data)->where("id", 4)->first()->prop_zone_name) ?? 0;
        $respons["d_prop_total_hh"] = (collect($data)->where("id", 4)->first()->prop_total_hh) ?? 0;
        $respons["d_prop_total_amount"] = (collect($data)->where("id", 4)->first()->prop_total_amount) ?? 0;

        $data = collect(DB::connection("pgsql")->select($sql_property_demand))->first();
        $respons["prop_current_demand"] = $data->prop_current_demand ?? 0;
        $respons["prop_arrear_demand"] = $data->prop_arrear_demand ?? 0;
        $respons["prop_total_demand"] = $data->prop_total_demand ?? 0;
        $data = collect(DB::connection("pgsql")->select($sql_property_collection))->first();
        // $respons["prop_current_collection"] = $data->prop_current_collection ?? 0;
        // $respons["prop_arrear_collection"] = $data->prop_arrear_collection ?? 0;
        $respons["prop_total_collection"] = $data->prop_total_collection ?? 0;

        $data = collect(DB::connection("pgsql")->select($sql_prop_vacant_land))->first();
        $respons["total_vacant_land"] = $data->total_vacant_land ?? 0;

        $data = collect(DB::connection("pgsql")->select($sql_prop_null_data))->first();
        $respons["null_prop_data"] = $data->null_prop_data ?? 0;
        $data = collect(DB::connection("pgsql")->select($sql_prop_null_floor_data))->first();
        $respons["null_floor_data"] = $data->null_floor_data ?? 0;
        return (object)$respons;
    }

    public function waterdetails()
    {                            
        $sql = "                                
                with   water_connection_underprocess as (                                                                          
                    select count(id) as water_connection_underprocess,ulb_id
                    from water_applications
                    where status = true
                    group by ulb_id
                ),
                water_detail as (
                SELECT 
                    COUNT(water_consumers.id) as water_total_consumer, 
                    ulb_id,
                    CASE 
                        WHEN water_consumer_meters.connection_type IN (1, 2) THEN 'meter' 
                        ELSE 'fix' 
                    END AS water_connection_type
                FROM 
                    water_consumers
                LEFT JOIN (
                    SELECT *
                    FROM water_consumer_meters
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM water_consumer_meters
                        WHERE status = 1
                        GROUP BY consumer_id
                    )
                ) water_consumer_meters ON water_consumer_meters.consumer_id = water_consumers.id
                WHERE 
                    water_consumers.status = 1
                GROUP BY 
                    CASE 
                        WHEN water_consumer_meters.connection_type IN (1, 2) THEN 'meter' 
                        ELSE 'fix' 
                    END,
                    ulb_id
                ),
                water_meter_detail as(
                        select * 
                        from water_detail
                        where water_connection_type = 'meter'
                ),
                water_fix_detail as(select * 
                            from water_detail
                            where water_connection_type = 'fix'
                ),
                meter_fix_connections as (
                    select ulb_masters.id as ulb_id,water_meter_detail.water_total_consumer as meter_water_total_consumer,
                        water_fix_detail.water_total_consumer as fix_water_total_consumer

                    from ulb_masters
                    left join water_meter_detail on water_meter_detail.ulb_id = ulb_masters.id
                    left join water_fix_detail on water_fix_detail.ulb_id = ulb_masters.id
                ),
                demand AS (
                    SELECT 
                        ulb_id,
                        SUM(CASE WHEN demand_from >= '2023-04-01' AND demand_upto <= '2024-03-31' THEN amount ELSE 0 END) AS current_demand,
                        SUM(CASE WHEN demand_upto < '2023-04-01' THEN amount ELSE 0 END) AS arrear_demand,
                        SUM(amount) AS total_demand,
                        COUNT(DISTINCT consumer_id) AS total_consumer
                    FROM 
                        water_consumer_demands
                    WHERE 
                        status = true AND demand_upto < '2024-03-31'
                    GROUP BY 
                        ulb_id
                ), 
                collection AS (
                    SELECT 
                        water_consumer_demands.ulb_id, 
                        SUM(water_consumer_demands.amount) AS total_collection,
                        SUM(CASE WHEN water_consumer_demands.demand_from >= '2023-04-01' AND water_consumer_demands.demand_upto <= '2024-03-31' THEN water_consumer_demands.amount ELSE 0 END) AS current_collection,
                        SUM(CASE WHEN water_consumer_demands.demand_upto < '2023-04-01' THEN water_consumer_demands.amount ELSE 0 END) AS arrear_collection,
                        COUNT(DISTINCT water_consumer_demands.consumer_id) AS total_coll_consumer
                    FROM  
                        water_tran_details
                        JOIN water_consumer_demands ON water_consumer_demands.id = water_tran_details.demand_id
                        JOIN water_trans ON water_trans.id = water_tran_details.tran_id
                    WHERE 
                        water_trans.tran_date BETWEEN '2023-04-01' AND '2024-03-31' AND water_trans.status IN (1, 2)
                        AND water_trans.tran_type = 'Demand Collection'
                        AND water_tran_details.status = 1
                    GROUP BY  
                        water_consumer_demands.ulb_id
                ), 
                prev_collection AS (
                    SELECT 
                        water_trans.ulb_id, 
                        SUM(water_consumer_demands.amount) AS total_prev_collection,
                        COUNT(DISTINCT water_trans.related_id) AS total_prev_coll_consumer
                    FROM  
                        water_tran_details
                        JOIN water_consumer_demands on water_consumer_demands.id = water_tran_details.demand_id
                        JOIN water_trans ON water_trans.id = water_tran_details.tran_id
                    WHERE 
                        water_trans.tran_date < '2023-04-01' AND water_trans.status IN (1, 2)
                        AND water_trans.tran_type = 'Demand Collection'
                        AND water_tran_details.status = 1
                    GROUP BY  
                        water_trans.ulb_id
                ),
                water_dcb as(
                    SELECT 
                        demand.ulb_id,
                        SUM(COALESCE(demand.current_demand, 0)) AS water_current_demand,
                        SUM(COALESCE(collection.current_collection, 0)) AS water_current_collection,
                        SUM(COALESCE(prev_collection.total_prev_collection, 0)) AS water_total_prev_collection,
                        (SUM(COALESCE(demand.arrear_demand, 0)) - SUM(COALESCE(prev_collection.total_prev_collection, 0))) AS water_arrear_demand,
                        SUM(COALESCE(collection.arrear_collection, 0)) AS water_arrear_collection,
                        (SUM(COALESCE(demand.current_demand, 0)) - SUM(COALESCE(collection.current_collection, 0))) AS water_current_outstanding,
                        ((SUM(COALESCE(demand.arrear_demand, 0)) - SUM(COALESCE(prev_collection.total_prev_collection, 0))) - SUM(COALESCE(collection.arrear_collection, 0))) AS water_arrear_outstanding,
                        SUM(COALESCE(collection.total_collection, 0)) AS water_total_collection,
                        CASE 
                            WHEN SUM(COALESCE(demand.current_demand, 0)) > 0 
                            THEN (SUM(COALESCE(collection.current_collection, 0)) / SUM(COALESCE(demand.current_demand, 0))) * 100
                            ELSE 0
                        END AS water_current_collection_efficiency,
                        CASE 
                            WHEN (SUM(COALESCE(demand.arrear_demand, 0)) - SUM(COALESCE(prev_collection.total_prev_collection, 0))) > 0 
                            THEN (SUM(COALESCE(collection.arrear_collection, 0)) / ((SUM(COALESCE(demand.arrear_demand, 0)) - SUM(COALESCE(prev_collection.total_prev_collection, 0)))) * 100)
                            ELSE 0
                        END AS water_arrear_collection_efficiency
                    FROM 
                        demand
                    LEFT JOIN 
                        collection ON collection.ulb_id = demand.ulb_id
                    LEFT JOIN 
                        prev_collection ON prev_collection.ulb_id = demand.ulb_id
                    GROUP BY 
                        demand.ulb_id
                )
                select id as ulb_id, ulb_name,
                    water_connection_underprocess.water_connection_underprocess, 
                    meter_fix_connections.meter_water_total_consumer as water_meter_connection_type, meter_fix_connections.fix_water_total_consumer as water_fix_connection_type, 
                    water_dcb.water_current_demand, water_dcb.water_arrear_demand, water_dcb.water_total_prev_collection, water_dcb.water_current_collection, 
                    water_dcb.water_arrear_collection, water_dcb.water_total_collection, water_dcb.water_current_collection_efficiency, 
                    water_dcb.water_arrear_collection_efficiency, water_dcb.water_current_outstanding, water_dcb.water_arrear_outstanding
                from ulb_masters
                left join water_connection_underprocess on water_connection_underprocess.ulb_id = ulb_masters.id
                left join meter_fix_connections on meter_fix_connections.ulb_id = ulb_masters.id
                left join water_dcb on water_dcb.ulb_id = ulb_masters.id
        
        ";
        $respons = collect(DB::connection("pgsql_water")->select($sql));
        // dd($respons);
        return (object)$respons;
    }

}



