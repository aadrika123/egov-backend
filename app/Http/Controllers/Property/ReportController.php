<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropDemand;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Auth;
use Carbon\Carbon;
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


        $data =  PropDemand::select(
            'holding_no',
            'new_holding_no',
            'owner_name',
            'mobile_no',
            'pt_no',
            'prop_address',
            'prop_demands.balance',
            'prop_demands.ward_mstr_id',
            'ward_name as ward_no',
            'fyear',
        )
            ->join('prop_properties', 'prop_properties.id', 'prop_demands.property_id')
            ->join('prop_owners', 'prop_owners.property_id', 'prop_demands.property_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_demands.ward_mstr_id')
            ->where('paid_status', 0)
            ->whereBetween('due_date', [$fromDate, $toDate])
            ->where('prop_demands.ulb_id', $ulbId)
            ->where('prop_demands.ward_mstr_id', $wardMstrId)
            ->groupby(
                'prop_demands.property_id',
                'holding_no',
                'new_holding_no',
                'pt_no',
                'prop_demands.balance',
                'prop_demands.ward_mstr_id',
                'fyear',
                'prop_address',
                'owner_name',
                'mobile_no',
                'ward_name'
            )
            ->get();
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
        return responseMsgs(true, "Ward Wise Holding Data!", $data, 'pr6.1', '1.1', $queryRunTime, 'Post', '');
    }

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
        return responseMsgs(true, "Financial Year List", $financialYears, '010801', '01', '382ms-547ms', 'Post', '');
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
}
