<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UlbMaster;
use App\Models\Property\PropDemand;
use App\Models\Property\PropTransaction;
use App\Models\UlbMaster as ModelsUlbMaster;
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
        $request->request->add(["metaData" => ["pr2.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr3.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safPropIndividualDemandAndCollection($request);
    }

    public function levelwisependingform(Request $request)
    {
        $request->request->add(["metaData" => ["pr4.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr4.2", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr4.2.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr4.2.2", 1.1, null, $request->getMethod(), null,]]);

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
        $request->request->add(["metaData" => ["pr4.2.1.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr5.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr1.2", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropPaymentModeWiseSummery($request);
    }

    public function PaymentModeWiseSummery(Request $request)
    {
        $request->merge(["metaData" => ["pr1.2", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr2.2", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr7.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDCB($request);
    }

    public function PropWardWiseDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr8.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr9.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr10.1", 1.1, null, $request->getMethod(), null,]]);
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
        return responseMsgs(true, "Ward Wise Holding Data!", $data, 'pr6.1', '1.1', $queryRunTime, 'Post', '');
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
        return responseMsgs(true, "Financial Year List", array_reverse($financialYears), 'pr11.1', '01', '382ms-547ms', 'Post', '');
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

            // if ($data->isEmpty())
            //     throw new Exception('No Data Found');

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

            return responseMsgs(true, 'Bulk Receipt', remove_null($receipts), '010801', '01', responseTime(), 'Post', '');
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
            // $page = $req->page && $req->page > 0 ? $req->page : 1;
            // $paginator = $gbsafCollection->paginate($perPage);
            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            return responseMsgs(true, "GB Saf Collection!", $list, 'pr12.1', '01', '623ms', 'Post', '');
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
        $request->request->add(["metaData" => ["pr13.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr14.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr15.1", 1.1, null, $request->getMethod(), null,]]);
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
        $request->request->add(["metaData" => ["pr16.1", 1.1, null, $request->getMethod(), null,]]);
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

        return responseMsgs(true, "", $data, "", "", "", "post", $request->deviceId);
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
            // $ulbId = $request->ulbId ?? authUser($request->ulb_id) ?? 2;
            // $prevYearData =  DB::connection('pgsql_reports')->table('mpl_prev_year')->where('ulb_id', $ulbId)->first();
            // $currentYearData =  DB::connection('pgsql_reports')->table('mpl_current_year')->where('ulb_id', $ulbId)->first();
            #_Assessed Properties
            // $data['Assessed Properties']['expected']                = "500";
            // $data['Assessed Properties']['achieved_upto_last_year'] = "400";
            // $data['Assessed Properties']['target_for_last_year']    = "350";
            // $data['Assessed Properties']['last_year_achievement']   = "300";
            // $data['Assessed Properties']['target_for_this_year']    = "350";
            // $data['Assessed Properties']['this_year_achievement']   = "350";

            // #_Saf Achievement
            // $data['Saf Achievement']['previous_year_target']        = "2000";
            // $data['Saf Achievement']['previous_year_achievement']   = "1800";
            // $data['Saf Achievement']['current_year_target']         = "2500";
            // $data['Saf Achievement']['current_year_achievement']    = "2200";

            // #_Assessment Categories
            // $data['Assessment Categories']['total_assessment']  = "50000";
            // $data['Assessment Categories']['residential']       = "30000";
            // $data['Assessment Categories']['commercial']        = "10000";
            // $data['Assessment Categories']['industrial']        = "5000";
            // $data['Assessment Categories']['gbsaf']             = "5000";

            // #_Ownership
            // $data['Ownership']['total_ownership'] = "250000";
            // $data['Ownership']['owned_property']  = "150000";
            // $data['Ownership']['rented_property'] = "50000";
            // $data['Ownership']['vacant_property'] = "50000";

            // #_Unpaid Properties
            // $data['Unpaid Properties']['count_not_paid_3yrs']  = "100000";
            // $data['Unpaid Properties']['amount_not_paid_3yrs'] = "5000000";
            // $data['Unpaid Properties']['count_not_paid_2yrs']  = "50000";
            // $data['Unpaid Properties']['amount_not_paid_2yrs'] = "1000000";
            // $data['Unpaid Properties']['count_not_paid_1yrs']  = "20000";
            // $data['Unpaid Properties']['amount_not_paid_1yrs']  = "500000";

            // #_Outstanding Demand Last Year
            // $data['Outstanding Demand Last Year']['outstanding']        = "4000000";
            // $data['Outstanding Demand Last Year']['outstanding_count']  = "200000";
            // $data['Outstanding Demand Last Year']['outstanding_amount'] = "500000";
            // $data['Outstanding Demand Last Year']['extempted']          = "600000";
            // $data['Outstanding Demand Last Year']['extempted_count']    = "844415";
            // $data['Outstanding Demand Last Year']['extempted_amount']   = "454544";
            // $data['Outstanding Demand Last Year']['recoverable_demand'] = "454545";
            // $data['Outstanding Demand Last Year']['payment_done']       = "50000";
            // $data['Outstanding Demand Last Year']['payment_due']        = "400000";

            // #_Outstanding Demand Current Year
            // $data['Outstanding Demand Current Year']['outstanding']        = "5000000";
            // $data['Outstanding Demand Current Year']['outstanding_count']  = "100000";
            // $data['Outstanding Demand Current Year']['outstanding_amount'] = "300000";
            // $data['Outstanding Demand Current Year']['extempted']          = "400000";
            // $data['Outstanding Demand Current Year']['extempted_count']    = "44415";
            // $data['Outstanding Demand Current Year']['extempted_amount']   = "554544";
            // $data['Outstanding Demand Current Year']['recoverable_demand'] = "4554545";
            // $data['Outstanding Demand Current Year']['payment_done']       = "50000";
            // $data['Outstanding Demand Current Year']['payment_due']        = "400000";

            // #_Payments
            // $data['Payments']['previous_to_last_year_payment_count']  = "60000";
            // $data['Payments']['previous_to_last_year_payment_amount'] = "600000";
            // $data['Payments']['last_year_payment_count']              = "70000";
            // $data['Payments']['last_year_payment_amount']             = "700000";
            // $data['Payments']['this_year_payment_count']              = "80000";
            // $data['Payments']['this_year_payment_amount']             = "800000";

            // #_Single Payment
            // $data['Single Payment']['before_previous_year_count'] = "50000";
            // $data['Single Payment']['previous_year_count']        = "60000";

            // #_Notice
            // $data['Notice']['last_year_count']     = "5000";
            // $data['Notice']['last_year_amount']    = "50000";
            // $data['Notice']['last_year_recovery']  = "40000";
            // $data['Notice']['this_year_count']     = "4000";
            // $data['Notice']['this_year_amount']    = "30000";
            // $data['Notice']['this_year_recovery']  = "20000";

            // #_Mutation
            // $data['Mutation']['last_year_count']  = "5000";
            // $data['Mutation']['last_year_amount'] = "50007";
            // $data['Mutation']['this_year_count']  = "8000";
            // $data['Mutation']['this_year_amount'] = "90000";

            // #_Top Areas Property Transactions
            // $data['Top Areas Property Transactions']['ward1_count'] = "500";
            // $data['Top Areas Property Transactions']['ward2_count'] = "450";
            // $data['Top Areas Property Transactions']['ward3_count'] = "430";
            // $data['Top Areas Property Transactions']['ward4_count'] = "390";
            // $data['Top Areas Property Transactions']['ward5_count'] = "350";

            // #_Top Areas Saf
            // $data['Top Areas Saf']['ward1_count'] = "600";
            // $data['Top Areas Saf']['ward2_count'] = "550";
            // $data['Top Areas Saf']['ward3_count'] = "520";
            // $data['Top Areas Saf']['ward4_count'] = "440";
            // $data['Top Areas Saf']['ward5_count'] = "420";

            // #_Payment Modes
            // $data['Payment Modes']['current_year_cash_collection']   = "6000000";
            // $data['Payment Modes']['last_year_cash_collection']      = "7000000";
            // $data['Payment Modes']['current_year_upi_collection']    = "4000000";
            // $data['Payment Modes']['last_year_upi_collection']       = "3000000";
            // $data['Payment Modes']['current_year_card_collection']   = "1000000";
            // $data['Payment Modes']['last_year_card_collection']      = "900000";
            // $data['Payment Modes']['current_year_cheque_collection'] = "9000000";
            // $data['Payment Modes']['last_year_cheque_collection']    = "8000000";
            // $data['Payment Modes']['current_year_dd_collection']     = "8800000";
            // $data['Payment Modes']['last_year_dd_collection']        = "7000000";

            // #_Citizen Engagement
            // $data['Citizen Engagement']['online_application_count_prev_year']  = "40000";
            // $data['Citizen Engagement']['online_application_count_this_year']  = "50000";
            // $data['Citizen Engagement']['online_application_amount_prev_year'] = "900000";
            // $data['Citizen Engagement']['online_application_amount_this_year'] = "800000";
            // $data['Citizen Engagement']['jsk_application_count_prev_year']     = "50000";
            // $data['Citizen Engagement']['jsk_application_count_this_year']     = "60000";
            // $data['Citizen Engagement']['jsk_application_amount_prev_year']    = "1000000";
            // $data['Citizen Engagement']['jsk_application_amount_this_year']    = "900000";

            // #_Compliances
            // $data['Compliances']['no_of_property_inspected_prev_year'] = "5000";
            // $data['Compliances']['no_of_defaulter_prev_year']          = "3000";
            // $data['Compliances']['no_of_property_inspected_this_year'] = "6000";
            // $data['Compliances']['no_of_defaulter_this_year']          = "3300";

            // $data['Demand']['prev_year']             = "3300";
            // $data['Demand']['current_year']          = "3300";
            // $data['Total Payment From HH']['prev_year']    = "3300";
            // $data['Total Payment From HH']['current_year'] = "3300";
            // return responseMsgs(true, "Mpl Report", $data, "", 01, responseTime(), $request->getMethod(), $request->deviceId);

            /**
             * =====================================================================================
             */

            $ulbId = $request->ulbId ?? 2;
            $prevYearData =  DB::connection('pgsql_reports')->table('mpl_prev_year')->where('ulb_id', $ulbId)->first();
            $currentYearData =  DB::connection('pgsql_reports')->table('mpl_current_year')->where('ulb_id', $ulbId)->first();

            #_Assessed Properties ??
            $data['Assessed Properties']['target_for_last_year']    = $prevYearData->assessed_property_target_for_last_year;
            $data['Assessed Properties']['last_year_achievement']   = $prevYearData->assessed_property_last_year_achievement;
            $data['Assessed Properties']['target_for_this_year']    = $currentYearData->assessed_property_target_for_this_year;
            $data['Assessed Properties']['this_year_achievement']   = $currentYearData->assessed_property_this_year_achievement;

            #_Saf Achievement
            $data['Saf Achievement']['previous_year_target']        = $prevYearData->saf_previous_year_target;
            $data['Saf Achievement']['previous_year_achievement']   = $prevYearData->saf_previous_year_achievement;
            $data['Saf Achievement']['current_year_target']         = $currentYearData->saf_current_year_target;
            $data['Saf Achievement']['current_year_achievement']    = $currentYearData->saf_current_year_achievement;

            #_Assessment Categories ??
            $data['Assessment Categories']['total_assessment']  = $currentYearData->total_assessment;
            $data['Assessment Categories']['residential']       = $currentYearData->total_assessed_residential;
            $data['Assessment Categories']['commercial']        = $currentYearData->total_assessed_commercial;
            $data['Assessment Categories']['industrial']        = $currentYearData->total_assessed_industrial;
            $data['Assessment Categories']['gbsaf']             = $currentYearData->total_assessed_gbsaf;

            #_Ownership ??
            $data['Ownership']['total_ownership'] = $currentYearData->total_ownership;
            $data['Ownership']['owned_property']  = $currentYearData->owned_property;
            $data['Ownership']['rented_property'] = $currentYearData->rented_property;
            $data['Ownership']['vacant_property'] = $currentYearData->vacant_property;

            #_Unpaid Properties
            $data['Unpaid Properties']['count_not_paid_3yrs']  = $prevYearData->count_not_paid_3yrs;
            $data['Unpaid Properties']['amount_not_paid_3yrs'] = round(($prevYearData->amount_not_paid_3yrs) / 100000, 2); #_in lacs
            $data['Unpaid Properties']['count_not_paid_2yrs']  = $prevYearData->count_not_paid_2yrs;
            $data['Unpaid Properties']['amount_not_paid_2yrs'] = round(($prevYearData->amount_not_paid_2yrs) / 100000, 2); #_in lacs
            $data['Unpaid Properties']['count_not_paid_1yrs']  = $prevYearData->count_not_paid_1yrs;
            $data['Unpaid Properties']['amount_not_paid_1yrs'] = round(($prevYearData->amount_not_paid_1yrs) / 100000, 2); #_in lacs

            #_Outstanding Demand Last Year
            // 604529369.42  =>total demand
            $data['Outstanding Demand Last Year']['outstanding']        = round(($prevYearData->outstanding) / 100000, 2);          #_in lacs
            $data['Outstanding Demand Last Year']['outstanding_count']  = $prevYearData->outstanding_count;
            $data['Outstanding Demand Last Year']['outstanding_amount'] = round(($prevYearData->outstanding_amount) / 100000, 2);   #_in lacs
            $data['Outstanding Demand Last Year']['extempted']          = round(($prevYearData->extempted) / 100000, 2);            #_in lacs
            $data['Outstanding Demand Last Year']['extempted_count']    = round(($prevYearData->extempted_count) / 100000, 2);      #_in lacs
            $data['Outstanding Demand Last Year']['extempted_amount']   = $prevYearData->extempted_amount;
            $data['Outstanding Demand Last Year']['recoverable_demand'] = round(($prevYearData->recoverable_demand) / 100000, 2);   #_in lacs   #_collection amount
            $data['Outstanding Demand Last Year']['payment_done']       = round(($prevYearData->payment_done) / 100000, 2);         #_in lacs
            $data['Outstanding Demand Last Year']['payment_due']        = round(($prevYearData->payment_due) / 100000, 2);          #_in lacs

            #_Outstanding Demand Current Year
            $data['Outstanding Demand Current Year']['outstanding']        = round(($currentYearData->demand_outstanding) / 100000, 2);             #_in lacs
            $data['Outstanding Demand Current Year']['outstanding_count']  = $currentYearData->demand_outstanding_count;
            $data['Outstanding Demand Current Year']['outstanding_amount'] = round(($currentYearData->demand_outstanding_amount) / 100000, 2);      #_in lacs
            $data['Outstanding Demand Current Year']['extempted']          = round(($currentYearData->demand_extempted) / 100000, 2);               #_in lacs
            $data['Outstanding Demand Current Year']['extempted_count']    = $currentYearData->demand_extempted_count;
            $data['Outstanding Demand Current Year']['extempted_amount']   = round(($currentYearData->demand_extempted_amount) / 100000, 2);        #_in lacs
            $data['Outstanding Demand Current Year']['recoverable_demand'] = round(($currentYearData->recoverable_demand) / 100000, 2);             #_in lacs
            $data['Outstanding Demand Current Year']['payment_done']       = round(($currentYearData->demand_payment_done) / 100000, 2);            #_in lacs
            $data['Outstanding Demand Current Year']['payment_due']        = round(($currentYearData->demand_payment_due) / 100000, 2);             #_in lacs

            #_Payments
            $data['Payments']['previous_to_last_year_payment_count']  = $prevYearData->previous_to_last_year_payment_count;
            $data['Payments']['previous_to_last_year_payment_amount'] = round(($prevYearData->previous_to_last_year_payment_amount) / 100000, 2);   #_in lacs
            $data['Payments']['last_year_payment_count']              = $prevYearData->last_year_payment_count;
            $data['Payments']['last_year_payment_amount']             = round(($prevYearData->last_year_payment_amount) / 100000, 2);               #_in lacs
            $data['Payments']['this_year_payment_count']              = $currentYearData->this_year_payment_count;
            $data['Payments']['this_year_payment_amount']             = round(($currentYearData->this_year_payment_amount) / 100000, 2);            #_in lacs

            #_Single Payment
            $data['Single Payment']['before_previous_year_count'] = $prevYearData->single_payment_before_previous_year_count;
            $data['Single Payment']['previous_year_count']        = $currentYearData->single_payment_current_year_count; // ?? one time payment in saf only

            #_Notice
            $data['Notice']['last_year_count']     = $prevYearData->notice_last_year_count;
            $data['Notice']['last_year_amount']    = round(($prevYearData->notice_last_year_amount) / 100000, 2);               #_in lacs
            $data['Notice']['last_year_recovery']  = round(($prevYearData->notice_last_year_recovery) / 100000, 2);             #_in lacs
            $data['Notice']['this_year_count']     = $currentYearData->notice_this_year_count;
            $data['Notice']['this_year_amount']    = round(($currentYearData->notice_this_year_amount) / 100000, 2);            #_in lacs
            $data['Notice']['this_year_recovery']  = round(($currentYearData->notice_this_year_recovery) / 100000, 2);          #_in lacs

            #_Mutation
            $data['Mutation']['last_year_count']  = $prevYearData->mutation_last_year_count;
            $data['Mutation']['last_year_amount'] = round(($prevYearData->mutation_last_year_amount) / 100000, 2);              #_in lacs
            $data['Mutation']['this_year_count']  = $currentYearData->mutation_this_year_count;
            $data['Mutation']['this_year_amount'] = round(($currentYearData->mutation_this_year_amount) / 100000, 2);           #_in lacs

            #_Top Areas Property Transactions 
            /**
             include ward no
             */
            $data['Top Areas Property Transactions']['ward1_count'] = $currentYearData->top_area_property_transaction_ward1_count;
            $data['Top Areas Property Transactions']['ward2_count'] = $currentYearData->top_area_property_transaction_ward2_count;
            $data['Top Areas Property Transactions']['ward3_count'] = $currentYearData->top_area_property_transaction_ward3_count;
            $data['Top Areas Property Transactions']['ward4_count'] = $currentYearData->top_area_property_transaction_ward4_count;
            $data['Top Areas Property Transactions']['ward5_count'] = $currentYearData->top_area_property_transaction_ward5_count;

            #_Top Areas Saf
            /**
             include ward no
             */
            $data['Top Areas Saf']['ward1_count'] = $currentYearData->top_area_saf_ward1_count;
            $data['Top Areas Saf']['ward2_count'] = $currentYearData->top_area_saf_ward2_count;
            $data['Top Areas Saf']['ward3_count'] = $currentYearData->top_area_saf_ward3_count;
            $data['Top Areas Saf']['ward4_count'] = $currentYearData->top_area_saf_ward4_count;
            $data['Top Areas Saf']['ward5_count'] = $currentYearData->top_area_saf_ward5_count;

            #_Payment Modes
            $data['Payment Modes']['current_year_cash_collection']   = round(($currentYearData->current_year_cash_collection) / 100000, 2);             #_in lacs
            $data['Payment Modes']['last_year_cash_collection']      = round(($prevYearData->last_year_cash_collection) / 100000, 2);                   #_in lacs
            $data['Payment Modes']['current_year_upi_collection']    = round(($currentYearData->current_year_upi_collection) / 100000, 2);              #_in lacs
            $data['Payment Modes']['last_year_upi_collection']       = round(($prevYearData->last_year_upi_collection) / 100000, 2);                    #_in lacs
            $data['Payment Modes']['current_year_card_collection']   = round(($currentYearData->current_year_card_collection) / 100000, 2);             #_in lacs
            $data['Payment Modes']['last_year_card_collection']      = round(($prevYearData->last_year_card_collection) / 100000, 2);                   #_in lacs
            $data['Payment Modes']['current_year_cheque_collection'] = round(($currentYearData->current_year_cheque_collection) / 100000, 2);           #_in lacs
            $data['Payment Modes']['last_year_cheque_collection']    = round(($prevYearData->last_year_cheque_collection) / 100000, 2);                 #_in lacs
            $data['Payment Modes']['current_year_dd_collection']     = round(($currentYearData->current_year_dd_collection) / 100000, 2);               #_in lacs
            $data['Payment Modes']['last_year_dd_collection']        = round(($prevYearData->last_year_dd_collection) / 100000, 2);                     #_in lacs

            #_Citizen Engagement
            $data['Citizen Engagement']['online_application_count_prev_year']  = $prevYearData->online_application_count_prev_year;
            $data['Citizen Engagement']['online_application_count_this_year']  = $currentYearData->online_application_count_this_year;
            $data['Citizen Engagement']['online_application_amount_prev_year'] = round(($prevYearData->online_application_amount_prev_year) / 100000, 2);       #_in lacs
            $data['Citizen Engagement']['online_application_amount_this_year'] = round(($currentYearData->online_application_amount_this_year) / 100000, 2);    #_in lacs
            $data['Citizen Engagement']['jsk_application_count_prev_year']     = $prevYearData->jsk_application_count_prev_year;
            $data['Citizen Engagement']['jsk_application_count_this_year']     = $currentYearData->jsk_application_count_this_year;
            $data['Citizen Engagement']['jsk_application_amount_prev_year']    = round(($prevYearData->jsk_application_amount_prev_year) / 100000, 2);          #_in lacs
            $data['Citizen Engagement']['jsk_application_amount_this_year']    = round(($currentYearData->jsk_application_amount_this_year) / 100000, 2);       #_in lacs

            #_Compliances
            $data['Compliances']['no_of_property_inspected_prev_year'] = $prevYearData->no_of_property_inspected_prev_year;
            $data['Compliances']['no_of_defaulter_prev_year']          = $prevYearData->no_of_defaulter_prev_year;
            $data['Compliances']['no_of_property_inspected_this_year'] = $currentYearData->no_of_property_inspected_this_year;
            $data['Compliances']['no_of_defaulter_this_year']          = $currentYearData->no_of_defaulter_this_year;

            $data['Demand']['prev_year']             = '604529369.42';
            $data['Demand']['current_year']          = '621337104.08';
            // $data['Collection']['prev_year']             = '604529369.42';
            // $data['Collection']['current_year']          = '621337104.08';
            // $data['Balance']['prev_year']             = '604529369.42';
            // $data['Balance']['current_year']          = '621337104.08';
            $data['Total Payment From HH']['prev_year']    = '129059';
            $data['Total Payment From HH']['current_year'] = '85413';


            return responseMsgs(true, "Mpl Report", $data, "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
