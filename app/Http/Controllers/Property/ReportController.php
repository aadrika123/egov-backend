<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropDemand;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use Auth;

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
            $respons["original"]["data"]["items"] = collect($respons["original"]["data"]["items"])->map(function ($val) use ($roles) {
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
        $ulbId = authUser()->ulb_id;
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
            "wardMstrId" => $wardMstrId
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
        $financialyear = $currentYear - 8;
        $financialYears = [];
        $start = Carbon::create($financialyear, 4, 1);
        $end = Carbon::now();

        while ($end >= $start) {
            // Determine the end of the current financial year
            $financialYearEnd = $end->month >= 4 ? Carbon::create($end->year, 3, 31) : Carbon::create($end->year - 1, 3, 31);
            $financialYear = $financialYearEnd->format('Y') . '-' . $end->format('Y');
            $financialYears[] = $financialYear;
            $end->subYear();
        }
        return responseMsgs(true, "Financial Year List", $financialYears, 'pr11.1', '01', '382ms-547ms', 'Post', '');
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

            $first_query = DB::table('prop_active_safs')
                ->select(
                    't.id',
                    'ward_name as ward_no',
                    'saf_no as application_no',
                    'ward_mstr_id',
                    'prop_address',
                    'tran_date',
                    'payment_mode as transaction_mode',
                    't.user_id as tc_id',
                    'user_name as emp_name',
                    'tran_no',
                    'cheque_no',
                    'bank_name',
                    'branch_name',
                    'amount',
                    DB::raw("CONCAT (from_fyear,'(',from_qtr,')','/',to_fyear,'(',to_qtr,')') AS payment_year"),
                )
                ->join('prop_transactions as t', 't.saf_id', 'prop_active_safs.id')
                ->join('users', 'users.id', 't.user_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_active_safs.ward_mstr_id')
                ->leftJoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 't.id')
                ->where('is_gb_saf', true)
                ->whereBetween('tran_date', [$fromDate, $uptoDate]);

            $gbsafCollection = DB::table('prop_safs')
                ->select(
                    't.id',
                    'ward_name as ward_no',
                    'saf_no as application_no',
                    'ward_mstr_id',
                    'prop_address',
                    'tran_date',
                    'payment_mode as transaction_mode',
                    't.user_id as tc_id',
                    'user_name as emp_name',
                    'tran_no',
                    'cheque_no',
                    'bank_name',
                    'branch_name',
                    'amount',
                    DB::raw("CONCAT (from_fyear,'(',from_qtr,')','/',to_fyear,'(',to_qtr,')') AS from_upto_fy_qtr"),

                )
                ->join('prop_transactions as t', 't.saf_id', 'prop_safs.id')
                ->join('users', 'users.id', 't.user_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_safs.ward_mstr_id')
                ->leftJoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 't.id')
                ->where('is_gb_saf', true)
                ->whereBetween('tran_date', [$fromDate, $uptoDate])
                ->union($first_query);

            if ($req->wardMstrId)
                $gbsafCollection = $gbsafCollection->where('ward_mstr_id', $req->wardMstrId);

            if ($req->paymentMode)
                $gbsafCollection = $gbsafCollection->where('payment_mode', $req->paymentMode);

            $perPage = $req->perPage ? $req->perPage : 10;
            $page = $req->page && $req->page > 0 ? $req->page : 1;
            $paginator = $gbsafCollection->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total / $perPage);
            $list = [
                "perPage" => $perPage,
                "page" => $page,
                "items" => $items,
                "total" => $total,
                "numberOfPages" => $numberOfPages
            ];

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




    #------------date 13/03/2023 -------------------------------------------------------------------------
    #   Code By Sandeep Bara
    #   Payment Mode Wise Collection Report
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
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr10.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDeactedList($request);
    }
}
