<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Property\PropTransaction;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery\CountValidator\Exact;

class Report implements IReport
{
    use SAF;
    use Workflow;

    protected $_common;
    protected $_modelWard;
    protected $_Saf;

    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
    }
    /**
     * | Property Collection
     */
    public function collectionReport(Request $request)
    {
        $metaData = collect($request->metaData)->all();

        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = PropTransaction::SELECT(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            prop_properties.id,
                            prop_transactions.id AS tran_id,
                            CONCAT('', prop_properties.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_properties.new_holding_no='' OR prop_properties.new_holding_no IS NULL THEN 'N/A' 
                                ELSE prop_properties.new_holding_no END
                            ) AS new_holding_no,
                            prop_owner_detail.owner_name,
                            prop_owner_detail.mobile_no,
                            CONCAT(
                                prop_transactions.from_fyear, '(', prop_transactions.from_qtr, ')', ' / ', 
                                prop_transactions.to_fyear, '(', prop_transactions.to_qtr, ')'
                            ) AS from_upto_fy_qtr,
                            prop_transactions.tran_date,
                            prop_transactions.payment_mode AS transaction_mode,
                            prop_transactions.amount,
                            (
                                CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
                                ELSE 'N/A' END
                            ) AS emp_name,
                            prop_transactions.tran_no,
                            (
                                CASE WHEN prop_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.cheque_no END
                            ) AS cheque_no,
                            (
                                CASE WHEN prop_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.bank_name END
                            ) AS bank_name,
                            (
                                CASE WHEN prop_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.branch_name END
                            ) AS branch_name
                "),
            )
                ->JOIN("prop_properties", "prop_properties.id", "prop_transactions.property_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, prop_owners.property_id 
                        FROM prop_owners 
                        JOIN prop_transactions on prop_transactions.property_id = prop_owners.property_id 
                        WHERE prop_transactions.property_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND prop_transactions.user_id = $userId " : "")
                        . ($paymentMode ? " AND upper(prop_transactions.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY prop_owners.property_id
                        ) AS prop_owner_detail
                        "),
                    function ($join) {
                        $join->on("prop_owner_detail.property_id", "=", "prop_transactions.property_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_properties.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.property_id")
                ->WHEREIN("prop_transactions.status", [1, 2])
                ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $data = $data->where("prop_transactions.user_id", $userId);
            }
            if ($paymentMode) {
                $data = $data->where(DB::raw("upper(prop_transactions.payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $data = $data->where("prop_transactions.ulb_id", $ulbId);
            }
            $paginator = collect();

            $data2 = $data;
            $totalHolding = $data2->count("prop_properties.id");
            $totalAmount = $data2->sum("prop_transactions.amount");
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $data->paginate($perPage);

            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalHolding" => $totalHolding,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
                // "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Saf collection
     */
    public function safCollection(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->is_gbsaf) {
                $isGbsaf = $request->is_gbsaf;
            }

            // DB::enableQueryLog();
            $activSaf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            prop_active_safs.id,
                            prop_transactions.id AS tran_id,
                            CONCAT('', prop_active_safs.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_active_safs.saf_no='' OR prop_active_safs.saf_no IS NULL THEN 'N/A' 
                                ELSE prop_active_safs.saf_no END
                            ) AS saf_no,
                            owner_detail.owner_name,
                            owner_detail.mobile_no,
                            CONCAT(
                                prop_transactions.from_fyear, '(', prop_transactions.from_qtr, ')', ' / ', 
                                prop_transactions.to_fyear, '(', prop_transactions.to_qtr, ')'
                            ) AS from_upto_fy_qtr,
                            prop_transactions.tran_date,
                            prop_transactions.payment_mode AS transaction_mode,
                            prop_transactions.amount,
                            (
                                CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
                                ELSE 'N/A' END
                            ) AS emp_name,
                            prop_transactions.tran_no,
                            (
                                CASE WHEN prop_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.cheque_no END
                            ) AS cheque_no,
                            (
                                CASE WHEN prop_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.bank_name END
                            ) AS bank_name,
                            (
                                CASE WHEN prop_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.branch_name END
                            ) AS branch_name
                "),
            )
                ->JOIN("prop_active_safs", "prop_active_safs.id", "prop_transactions.saf_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_active_safs_owners.saf_id 
                        FROM prop_active_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_active_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND prop_transactions.user_id = $userId " : "")
                        . ($paymentMode ? " AND upper(prop_transactions.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY prop_active_safs_owners.saf_id 
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.saf_id", "=", "prop_transactions.saf_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_active_safs.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status", [1, 2])
                ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);

            $rejectedSaf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            prop_rejected_safs.id,
                            prop_transactions.id AS tran_id,
                            CONCAT('', prop_rejected_safs.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_rejected_safs.saf_no='' OR prop_rejected_safs.saf_no IS NULL THEN 'N/A' 
                                ELSE prop_rejected_safs.saf_no END
                            ) AS saf_no,
                            owner_detail.owner_name,
                            owner_detail.mobile_no,
                            CONCAT(
                                prop_transactions.from_fyear, '(', prop_transactions.from_qtr, ')', ' / ', 
                                prop_transactions.to_fyear, '(', prop_transactions.to_qtr, ')'
                            ) AS from_upto_fy_qtr,
                            prop_transactions.tran_date,
                            prop_transactions.payment_mode AS transaction_mode,
                            prop_transactions.amount,
                            (
                                CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
                                ELSE 'N/A' END
                            ) AS emp_name,
                            prop_transactions.tran_no,
                            (
                                CASE WHEN prop_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.cheque_no END
                            ) AS cheque_no,
                            (
                                CASE WHEN prop_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.bank_name END
                            ) AS bank_name,
                            (
                                CASE WHEN prop_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.branch_name END
                            ) AS branch_name
                "),
            )
                ->JOIN("prop_rejected_safs", "prop_rejected_safs.id", "prop_transactions.saf_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_rejected_safs_owners.saf_id 
                        FROM prop_rejected_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_rejected_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND prop_transactions.user_id = $userId " : "")
                        . ($paymentMode ? " AND upper(prop_transactions.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY prop_rejected_safs_owners.saf_id 
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.saf_id", "=", "prop_transactions.saf_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_rejected_safs.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status", [1, 2])
                ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);

            $saf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            prop_safs.id,
                            prop_transactions.id AS tran_id,
                            CONCAT('', prop_safs.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_safs.saf_no='' OR prop_safs.saf_no IS NULL THEN 'N/A' 
                                ELSE prop_safs.saf_no END
                            ) AS saf_no,
                            owner_detail.owner_name,
                            owner_detail.mobile_no,
                            CONCAT(
                                prop_transactions.from_fyear, '(', prop_transactions.from_qtr, ')', ' / ', 
                                prop_transactions.to_fyear, '(', prop_transactions.to_qtr, ')'
                            ) AS from_upto_fy_qtr,
                            prop_transactions.tran_date,
                            prop_transactions.payment_mode AS transaction_mode,
                            prop_transactions.amount,
                            (
                                CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
                                ELSE 'N/A' END
                            ) AS emp_name,
                            prop_transactions.tran_no,
                            (
                                CASE WHEN prop_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.cheque_no END
                            ) AS cheque_no,
                            (
                                CASE WHEN prop_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.bank_name END
                            ) AS bank_name,
                            (
                                CASE WHEN prop_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                ELSE prop_cheque_dtls.branch_name END
                            ) AS branch_name
                "),
            )
                ->JOIN("prop_safs", "prop_safs.id", "prop_transactions.saf_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_safs_owners.saf_id 
                        FROM prop_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND prop_transactions.user_id = $userId " : "")
                        . ($paymentMode ? " AND upper(prop_transactions.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY prop_safs_owners.saf_id 
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.saf_id", "=", "prop_transactions.saf_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_safs.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status", [1, 2])
                ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);

            if ($wardId) {
                $activSaf = $activSaf->where("ulb_ward_masters.id", $wardId);
                $rejectedSaf = $rejectedSaf->where("ulb_ward_masters.id", $wardId);
                $saf = $saf->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $activSaf = $activSaf->where("prop_transactions.user_id", $userId);
                $rejectedSaf = $rejectedSaf->where("prop_transactions.user_id", $userId);
                $saf = $saf->where("prop_transactions.user_id", $userId);
            }
            if ($paymentMode) {
                $activSaf = $activSaf->where(DB::row("prop_transactions.upper(payment_mode)"), $paymentMode);
                $rejectedSaf = $rejectedSaf->where(DB::row("prop_transactions.upper(payment_mode)"), $paymentMode);
                $saf = $saf->where(DB::row("prop_transactions.upper(payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $activSaf = $activSaf->where("prop_transactions.ulb_id", $ulbId);
                $rejectedSaf = $rejectedSaf->where("prop_transactions.ulb_id", $ulbId);
                $saf = $saf->where("prop_transactions.ulb_id", $ulbId);
            }

            $data = $activSaf->union($rejectedSaf)->union($saf);
            $data2 = $data;
            $totalSaf = $data2->count("id");
            $totalAmount = $data2->sum("amount");
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                // "perPage" => $perPage,
                // "page" => $page,
                // "totalSaf" => $totalSaf,
                // "totalAmount" => $totalAmount,
                // "items" => $items,
                // "total" => $total,
                // "numberOfPages" => $numberOfPages

                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalSaf" => $totalSaf,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function safPropIndividualDemandAndCollection(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $key = null;
            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            if ($request->key) {
                $key = $request->key;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = PropProperty::select(
                DB::raw("ulb_ward_masters.ward_name as ward_no,
                        prop_properties.holding_no,
                        (
                            CASE WHEN prop_properties.new_holding_no IS NULL OR prop_properties.new_holding_no='' THEN 'N/A' 
                            ELSE prop_properties.new_holding_no END
                        ) AS new_holding_no,
                        (
                            CASE WHEN prop_safs.saf_no IS NULL  THEN 'N/A' 
                            ELSE prop_safs.saf_no END
                        ) AS saf_no,
                        owner_detail.owner_name,
                        owner_detail.mobile_no,
                        prop_properties.prop_address,
                        (
                            CASE WHEN prop_safs.assessment_type IS NULL  THEN 'N/A' 
                            ELSE prop_safs.assessment_type END
                        ) AS assessment_type,
                        (
                            CASE WHEN floor_details.usage_type IS NULL THEN 'N/A' 
                            ELSE floor_details.usage_type END
                        ) AS usage_type,
                        (
                            CASE WHEN floor_details.construction_type IS NULL 
                            THEN 'N/A' ELSE floor_details.construction_type END
                        ) AS construction_type,
                        (
                            CASE WHEN demands.arrear_demand IS NULL THEN '0.00'
                            ELSE demands.arrear_demand END
                        ) AS arrear_demand,
                        (
                            CASE WHEN demands.current_demand IS NULL THEN '0.00' 
                            ELSE demands.current_demand END
                        ) AS current_demand,
                        COALESCE(demands.arrear_demand, 0)
                            +COALESCE(demands.current_demand, 0)
                            AS total_demand,                        
                        (
                            CASE WHEN collection.arrear_collection IS NULL THEN '0.00' 
                            ELSE collection.arrear_collection END
                        ) AS arrear_collection,
                        (
                            CASE WHEN collection.current_collection IS NULL THEN '0.00' 
                            ELSE collection.current_collection END
                        ) AS current_collection,
                        COALESCE(collection.arrear_collection, 0)
                            +COALESCE(collection.current_collection, 0) 
                            AS total_collection,
                        (
                            CASE WHEN tbl_penalty.penalty IS NULL THEN '0.00'
                            ELSE tbl_penalty.penalty END
                        ) AS penalty,
                        (
                            CASE WHEN tbl_rebate.rebate IS NULL THEN '0.00' 
                            ELSE tbl_rebate.rebate END
                        ) AS rebate,
                        '0.00' AS advance,
                        '0.00' AS adjust,
                        (
                            COALESCE(demands.arrear_demand, 0)+COALESCE(demands.current_demand, 0)
                        )
                        -
                        (
                           COALESCE(collection.arrear_collection, 0)+COALESCE(collection.current_collection, 0)
                        ) AS total_due
                "),
            )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_properties.ward_mstr_id")
                ->LEFTJOIN("prop_safs", "prop_safs.id", "prop_properties.saf_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT 
                            prop_owners.property_id, 
                            STRING_AGG(prop_owners.owner_name, ',') AS owner_name, 
                            STRING_AGG(prop_owners.mobile_no::TEXT, ',') AS mobile_no 
                        FROM prop_owners
                        WHERE prop_owners.status=1
                        GROUP BY prop_owners.property_id
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.property_id", "=", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("(
                        SELECT 
                            prop_floors.property_id, 
                            STRING_AGG(ref_prop_usage_types.usage_type, ',') AS usage_type, 
                            STRING_AGG(ref_prop_construction_types.construction_type, ',') AS construction_type 
                        FROM prop_floors
                        INNER JOIN ref_prop_usage_types ON ref_prop_usage_types.id=prop_floors.usage_type_mstr_id
                        INNER JOIN ref_prop_construction_types ON ref_prop_construction_types.id=prop_floors.const_type_mstr_id
                        WHERE prop_floors.status=1 
                        GROUP BY prop_floors.property_id
                        )AS floor_details
                        "),
                    function ($join) {
                        $join->on("floor_details.property_id", "=", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("(
                    SELECT 
                        property_id, 
                        SUM(CASE WHEN fyear < '$fiYear' THEN amount ELSE 0 END) AS arrear_demand,
                        SUM(CASE WHEN fyear = '$fiYear' THEN amount ELSE 0 END) AS current_demand
                    FROM prop_demands 
                    WHERE status=1 AND paid_status IN (0,1)  
                    GROUP BY property_id
                    )AS demands
                    "),
                    function ($join) {
                        $join->on("demands.property_id", "=", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("(
                    SELECT 
                        property_id, 
                        SUM(CASE WHEN fyear < '$fiYear' THEN amount ELSE 0 END) AS arrear_collection,
                        SUM(CASE WHEN fyear = '$fiYear' THEN amount ELSE 0 END) AS current_collection
                    FROM prop_demands 
                    WHERE status=1 AND paid_status=1 
                    GROUP BY property_id
                    )AS collection
                    "),
                    function ($join) {
                        $join->on("collection.property_id", "=", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("(
                    SELECT
                        prop_transactions.property_id AS property_id,
                        SUM(prop_penaltyrebates.amount) AS penalty
                    FROM prop_penaltyrebates
                    INNER JOIN prop_transactions ON prop_transactions.id=prop_penaltyrebates.tran_id
                    WHERE prop_transactions.property_id is not null 
                            AND prop_penaltyrebates.status=1 
                            AND prop_penaltyrebates.is_rebate = FALSE
                    GROUP BY prop_transactions.property_id
                    )AS tbl_penalty
                    "),
                    function ($join) {
                        $join->on("tbl_penalty.property_id", "=", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("(
                    SELECT
                        prop_transactions.property_id AS property_id,
                        SUM(prop_penaltyrebates.amount) AS rebate
                    FROM prop_penaltyrebates
                    INNER JOIN prop_transactions ON prop_transactions.id=prop_penaltyrebates.tran_id
                    WHERE prop_transactions.property_id is not null 
                            AND prop_penaltyrebates.status=1 
                            AND prop_penaltyrebates.is_rebate=true
                    GROUP BY prop_transactions.property_id
                    )AS tbl_rebate
                    "),
                    function ($join) {
                        $join->on("tbl_rebate.property_id", "=", "prop_properties.id");
                    }
                )
                ->WHERE("prop_properties.status", 1)
                ->WHERE(function ($where) use ($key) {
                    $where->ORWHERE('prop_properties.holding_no', 'ILIKE', '%' . $key . '%')
                        ->ORWHERE('prop_properties.new_holding_no', 'ILIKE', '%' . $key . '%')
                        ->ORWHERE('prop_safs.saf_no', 'ILIKE', '%' . $key . '%')
                        ->ORWHERE('owner_detail.owner_name', 'ILIKE', '%' . $key . '%')
                        ->ORWHERE('owner_detail.mobile_no', 'ILIKE', '%' . $key . '%')
                        ->ORWHERE('prop_properties.prop_address', 'ILIKE', '%' . $key . '%');
                });
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($ulbId) {
                $data = $data->where("prop_properties.ulb_id", $ulbId);
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                // "perPage" => $perPage,
                // "page" => $page,
                // "items" => $items,
                // "total" => $total,
                // "numberOfPages" => $numberOfPages

                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function levelwisependingform(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = WfRole::SELECT(
                "wf_roles.id",
                "wf_roles.role_name",
                DB::RAW("COUNT(prop_active_safs.id) AS total")
            )
                ->JOIN(DB::RAW("(
                                        SELECT distinct(wf_role_id) as wf_role_id
                                        FROM wf_workflowrolemaps 
                                        WHERE  wf_workflowrolemaps.is_suspended = false AND (forward_role_id IS NOT NULL OR backward_role_id IS NOT NULL)
                                            AND workflow_id IN(3,4,5) 
                                        GROUP BY wf_role_id 
                                ) AS roles
                    "), function ($join) {
                    $join->on("wf_roles.id", "roles.wf_role_id");
                })
                ->LEFTJOIN("prop_active_safs", function ($join) use ($ulbId) {
                    $join->ON("prop_active_safs.current_role", "roles.wf_role_id")
                        ->WHERE("prop_active_safs.ulb_id", $ulbId)
                        ->WHERE(function ($where) {
                            $where->ORWHERE("prop_active_safs.payment_status", "<>", 0)
                                ->ORWHERENOTNULL("prop_active_safs.payment_status");
                        });
                })
                ->GROUPBY(["wf_roles.id", "wf_roles.role_name"])
                ->UNION(
                    PropActiveSaf::SELECT(
                        DB::RAW("8 AS id, 'JSK' AS role_name,
                                COUNT(prop_active_safs.id)")
                    )
                        ->WHERE("prop_active_safs.ulb_id", $ulbId)
                        ->WHERENOTNULL("prop_active_safs.user_id")
                        ->WHERE(function ($where) {
                            $where->WHERE("prop_active_safs.payment_status", "=", 0)
                                ->ORWHERENULL("prop_active_safs.payment_status");
                        })
                )
                ->GET();
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function levelformdetail(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $roleId = $roleId2 = $userId = null;
            $mWardPermission = collect([]);

            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->roleId) {
                $roleId = $request->roleId;
            }
            if ($request->userId) {
                $userId = $request->userId;
                $roleId2 = ($this->_common->getUserRoll($userId, $ulbId, $safWorkFlow))->role_id ?? 0;
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) {
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2 ? $roleId2 : $roleId;
            if (!in_array($roleId, [11, 8])) {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }

            $mWardIds = $mWardPermission->implode("ward_id", ",");
            $mWardIds = explode(',', ($mWardIds ? $mWardIds : "0"));
            // DB::enableQueryLog();
            $data = PropActiveSaf::SELECT(
                DB::RAW("wf_roles.id AS role_id, wf_roles.role_name,
                    prop_active_safs.id, prop_active_safs.saf_no, prop_active_safs.prop_address,
                    ward_name as ward_no, 
                    owner.owner_name, owner.mobile_no")
            )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_active_safs.ward_mstr_id")
                ->LEFTJOIN(DB::RAW("( 
                                SELECT DISTINCT(prop_active_safs_owners.saf_id) AS saf_id,STRING_AGG(owner_name,',') AS owner_name, 
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no
                                FROM prop_active_safs_owners
                                JOIN prop_active_safs ON prop_active_safs.id = prop_active_safs_owners.saf_id
                                WHERE prop_active_safs_owners.status = 1 
                                    AND prop_active_safs.ulb_id = $ulbId
                                GROUP BY prop_active_safs_owners.saf_id
                                ) AS owner
                                "), function ($join) {
                    $join->on("owner.saf_id", "=", "prop_active_safs.id");
                });
            if ($roleId == 8) {
                $data = $data->LEFTJOIN("wf_roles", "wf_roles.id", "prop_active_safs.current_role")
                    ->WHERENOTNULL("prop_active_safs.user_id")
                    ->WHERE(function ($where) {
                        $where->WHERE("prop_active_safs.payment_status", "=", 0)
                            ->ORWHERENULL("prop_active_safs.payment_status");
                    });
            } else {
                $data = $data->JOIN("wf_roles", "wf_roles.id", "prop_active_safs.current_role")
                    ->WHERE("wf_roles.id", $roleId);
            }
            $data = $data->WHERE("prop_active_safs.ulb_id", $ulbId);
            if (!in_array($roleId, [11, 8]) && $userId) {
                $data = $data->WHEREIN("prop_active_safs.ward_mstr_id", $mWardIds);
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
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
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function levelUserPending(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $roleId = $roleId2 = $userId = null;
            $joins = "join";

            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->roleId) {
                $roleId = $request->roleId;
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) {
                throw new Exception("Invalid RoleId Pass");
            }
            if (in_array($roleId, [11, 8])) {
                $joins = "leftjoin";
            }

            // DB::enableQueryLog();
            $data = PropActiveSaf::SELECT(
                DB::RAW(
                    "count(prop_active_safs.id),
                    users_role.user_id ,
                    users_role.user_name,
                    users_role.wf_role_id as role_id,
                    users_role.role_name"
                )
            )
                ->$joins(
                    DB::RAW("(
                        select wf_role_id,user_id,user_name,role_name,concat('{',ward_ids,'}') as ward_ids
                        from (
                            select wf_roleusermaps.wf_role_id,wf_roleusermaps.user_id,
                            users.user_name, wf_roles.role_name,
                            string_agg(wf_ward_users.ward_id::text,',') as ward_ids
                            from wf_roleusermaps 
                            join wf_roles on wf_roles.id = wf_roleusermaps.wf_role_id
                                AND wf_roles.status =1
                            join users on users.id = wf_roleusermaps.user_id
                            left join wf_ward_users on wf_ward_users.user_id = wf_roleusermaps.user_id and wf_ward_users.is_suspended = false
                            where wf_roleusermaps.wf_role_id =$roleId
                                AND wf_roleusermaps.is_suspended = false
                            group by wf_roleusermaps.wf_role_id,wf_roleusermaps.user_id,users.user_name,wf_roles.role_name
                        )role_user_ward
                    ) users_role
                    "),
                    function ($join) use ($joins) {
                        if ($joins == "join") {
                            $join->on("users_role.wf_role_id", "=", "prop_active_safs.current_role")
                                ->where("prop_active_safs.ward_mstr_id", DB::raw("ANY (ward_ids::int[])"));
                        } else {
                            $join->on(DB::raw("1"), DB::raw("1"));
                        }
                    }
                )
                ->WHERE("prop_active_safs.ulb_id", $ulbId)
                ->groupBy(["users_role.user_id", "users_role.user_name", "users_role.wf_role_id", "users_role.role_name"]);

            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
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
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
    public function userWiseWardWireLevelPending(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $roleId = $roleId2 = $userId = null;
            $mWardPermission = collect([]);

            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->roleId) {
                $roleId = $request->roleId;
            }
            if ($request->userId) {
                $userId = $request->userId;
                $roleId2 = ($this->_common->getUserRoll($userId, $ulbId, $safWorkFlow))->role_id ?? 0;
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) {
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2 ? $roleId2 : $roleId;
            if (!in_array($roleId, [11, 8])) {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }

            $mWardIds = $mWardPermission->implode("ward_id", ",");
            $mWardIds = explode(',', ($mWardIds ? $mWardIds : "0"));
            // DB::enableQueryLog();
            $data = UlbWardMaster::SELECT(
                DB::RAW(" DISTINCT(ward_name) as ward_no, COUNT(prop_active_safs.id) AS total")
            )
                ->LEFTJOIN("prop_active_safs", "ulb_ward_masters.id", "prop_active_safs.ward_mstr_id");
            if ($roleId == 8) {
                $data = $data->LEFTJOIN("wf_roles", "wf_roles.id", "prop_active_safs.current_role")
                    ->WHERENOTNULL("prop_active_safs.user_id")
                    ->WHERE(function ($where) {
                        $where->WHERE("prop_active_safs.payment_status", "=", 0)
                            ->ORWHERENULL("prop_active_safs.payment_status");
                    });
            } else {
                $data = $data->JOIN("wf_roles", "wf_roles.id", "prop_active_safs.current_role")
                    ->WHERE("wf_roles.id", $roleId);
            }
            if (!in_array($roleId, [11, 8]) && $userId) {
                $data = $data->WHEREIN("prop_active_safs.ward_mstr_id", $mWardIds);
            }
            $data = $data->WHERE("prop_active_safs.ulb_id", $ulbId);
            $data = $data->groupBy(["ward_name"]);

            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
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
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }


    public function safSamFamGeotagging(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            $where = " WHERE status = 1 AND  ulb_id = $ulbId
                        AND created_at::date BETWEEN '$fromDate' AND '$uptoDate'";
            if ($wardId) {
                $where .= " AND ward_mstr_id = $wardId ";
            }
            $sql = "
                WITH saf AS (
                    SELECT 
                    distinct saf.* 
                    FROM(
                            (
                                select prop_active_safs.id as id, 
                                    prop_active_safs.ward_mstr_id,
                                    prop_active_safs.parked
                                from prop_active_safs                                     
                                $where
                            )
                            UNION (    
                                select prop_safs.id as id, 
                                    prop_safs.ward_mstr_id,
                                    prop_safs.parked
                                from prop_safs
                                $where
                
                            )
                            UNION (    
                                select prop_rejected_safs.id as id, 
                                    prop_rejected_safs.ward_mstr_id,
                                    prop_rejected_safs.parked
                                from prop_rejected_safs
                                $where
                            )
                    ) saf
                    join prop_transactions on prop_transactions.saf_id = saf.id 
                    and prop_transactions.status in(1,2)
                    GROUP BY saf.id,ward_mstr_id,parked
                ),
                memos AS (
                        select prop_saf_memo_dtls.saf_id,
                            prop_saf_memo_dtls.memo_type,
                            prop_saf_memo_dtls.created_at::date as created_at
                        FROM prop_saf_memo_dtls
                        JOIN saf ON saf.id = prop_saf_memo_dtls.saf_id
                        
                ),
                geotaging as (
                    select prop_saf_geotag_uploads.saf_id
                    from prop_saf_geotag_uploads
                    join saf on saf.id = prop_saf_geotag_uploads.saf_id
                    where prop_saf_geotag_uploads.status = 1
                    group by prop_saf_geotag_uploads.saf_id
                )
                
                select 
                    count(distinct(saf.id)) total_saf,
                    count(distinct( case when memos.memo_type = 'SAM' then memos.saf_id else null end)) as total_sam,
                    count( distinct(case when memos.memo_type = 'FAM' then memos.saf_id else null end)) as total_fam,
                    count( distinct(case when saf.parked = true then memos.saf_id else null end)) as total_btc,
                    count(distinct(geotaging.saf_id)) total_geotaging,
                    COALESCE(count(distinct(saf.id)) -  count(distinct( case when memos.memo_type = 'SAM' then memos.saf_id else null end))) as pending_sam,
                    COALESCE(count(distinct(saf.id)) -  count(distinct( case when memos.memo_type = 'FAM' then memos.saf_id else null end))) as pending_fam,
                    ward_name as ward_no
                    
                from saf
                join ulb_ward_masters on ulb_ward_masters.id = saf.ward_mstr_id
                LEFT JOIN memos ON memos.saf_id = saf.id
                LEFT JOIN geotaging ON geotaging.saf_id = saf.id
                group by ward_name
                ORDER BY  ward_name
            ";
            $data = DB::select($sql);
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));

            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function PropPaymentModeWiseSummery(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            $collection = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.property_id)) AS holding_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN("prop_transactions", function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode) "))
                        ->WHERENOTNULL("prop_transactions.property_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $collection = $collection->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $refund = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.property_id)) AS holding_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN("prop_transactions", function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode) "))
                        ->WHERENOTNULL("prop_transactions.property_id")
                        ->WHERENOTIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $refund = $refund->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $doorToDoor = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                    WHERE UPPER(prop_transactions.payment_mode) <> UPPER('ONLINE')
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.property_id)) AS holding_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN(DB::RAW("(
                                     SELECT * 
                                     FROM prop_transactions
                                     JOIN (
                                        
                                            SELECT wf_roleusermaps.user_id as role_user_id
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
                                                AND wf_workflows.id in (3,4,5)
                                            GROUP BY wf_roleusermaps.user_id
                                            ORDER BY wf_roleusermaps.user_id
                                     ) collecter on prop_transactions.user_id  = collecter.role_user_id
                                ) prop_transactions"), function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode)"))
                        ->WHERENOTNULL("prop_transactions.property_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $doorToDoor = $doorToDoor->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $jsk = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                    WHERE UPPER(prop_transactions.payment_mode) <> UPPER('ONLINE')
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.property_id)) AS holding_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN(DB::RAW("(
                                        SELECT * 
                                        FROM prop_transactions
                                        JOIN (
                                            
                                                SELECT wf_roleusermaps.user_id as role_user_id
                                                FROM wf_roles
                                                JOIN wf_roleusermaps ON wf_roleusermaps.wf_role_id = wf_roles.id 
                                                    AND wf_roleusermaps.is_suspended = FALSE
                                                JOIN wf_workflowrolemaps ON wf_workflowrolemaps.wf_role_id = wf_roleusermaps.wf_role_id
                                                    AND wf_workflowrolemaps.is_suspended = FALSE
                                                JOIN wf_workflows ON wf_workflows.id = wf_workflowrolemaps.workflow_id AND wf_workflows.is_suspended = FALSE 
                                                JOIN ulb_masters ON ulb_masters.id = wf_workflows.ulb_id
                                                WHERE wf_roles.is_suspended = FALSE 
                                                    AND wf_workflows.ulb_id = 2
                                                    AND wf_roles.id in (8,108)
                                                    AND wf_workflows.id in (3,4,5)
                                                GROUP BY wf_roleusermaps.user_id
                                                ORDER BY wf_roleusermaps.user_id
                                        ) collecter on prop_transactions.user_id  = collecter.role_user_id
                                    ) prop_transactions"), function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode)"))
                        ->WHERENOTNULL("prop_transactions.property_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $jsk = $jsk->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }

            $collection = $collection->get();
            $refund     = $refund->get();
            $doorToDoor = $doorToDoor->get();
            $jsk        = $jsk->get();

            $totalCollection = $collection->sum("amount");
            $totalHolding = $collection->sum("holding_count");
            $totalTran = $collection->sum("tran_count");

            $totalCollectionRefund = $refund->sum("amount");
            $totalHoldingRefund = $refund->sum("holding_count");
            $totalTranRefund = $refund->sum("tran_count");

            $totalCollectionDoor = $doorToDoor->sum("amount");
            $totalHoldingDoor = $doorToDoor->sum("holding_count");
            $totalTranDoor = $doorToDoor->sum("tran_count");

            $totalCollectionJsk = $jsk->sum("amount");
            $totalHoldingJsk = $jsk->sum("holding_count");
            $totalTranJsk = $jsk->sum("tran_count");

            $collection[] = [
                "transaction_mode" => "Total Collection",
                "holding_count"    => $totalHolding,
                "tran_count"       => $totalTran,
                "amount"           => $totalCollection
            ];
            $funal["collection"] = $collection;
            $refund[] = [
                "transaction_mode" => "Total Refund",
                "holding_count"    => $totalHoldingRefund,
                "tran_count"       => $totalTranRefund,
                "amount"           => $totalCollectionRefund
            ];
            $funal["refund"] = $refund;
            $funal["netCollection"][] = [
                "transaction_mode" => "Net Collection",
                "holding_count"    => $totalHolding - $totalHoldingRefund,
                "tran_count"       => $totalTran - $totalTranRefund,
                "amount"           => $totalCollection - $totalCollectionRefund
            ];

            $doorToDoor[] = [
                "transaction_mode" => "Total Door To Door",
                "holding_count"    => $totalCollectionDoor,
                "tran_count"       => $totalHoldingDoor,
                "amount"           => $totalTranDoor
            ];
            $funal["doorToDoor"] = $doorToDoor;

            $jsk[] = [
                "transaction_mode" => "Total JSK",
                "holding_count"    => $totalCollectionJsk,
                "tran_count"       => $totalHoldingJsk,
                "amount"           => $totalTranJsk
            ];
            $funal["jsk"] = $jsk;
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $funal, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function SafPaymentModeWiseSummery(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            $collection = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.saf_id)) AS saf_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN("prop_transactions", function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode) "))
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $collection = $collection->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $refund = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.saf_id)) AS saf_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN("prop_transactions", function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode) "))
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHERENOTIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $refund = $refund->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $doorToDoor = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                    WHERE UPPER(prop_transactions.payment_mode) <> UPPER('ONLINE')
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.saf_id)) AS saf_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN(DB::RAW("(
                                     SELECT * 
                                     FROM prop_transactions
                                     JOIN (
                                        
                                            SELECT wf_roleusermaps.user_id as role_user_id
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
                                                AND wf_workflows.id in (3,4,5)
                                            GROUP BY wf_roleusermaps.user_id
                                            ORDER BY wf_roleusermaps.user_id
                                     ) collecter on prop_transactions.user_id  = collecter.role_user_id
                                ) prop_transactions"), function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode)"))
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $doorToDoor = $doorToDoor->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $jsk = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(prop_transactions.payment_mode)) AS mode 
                                    FROM prop_transactions
                                    WHERE UPPER(prop_transactions.payment_mode) <> UPPER('ONLINE')
                                ) payment_modes")
            )
                ->select(
                    DB::raw("payment_modes.mode AS transaction_mode,
                        COUNT(DISTINCT(prop_transactions.saf_id)) AS saf_count,
                        COUNT(prop_transactions.id) AS tran_count, 
                        SUM(COALESCE(prop_transactions.amount,0)) AS amount
                        ")
                )
                ->LEFTJOIN(DB::RAW("(
                                        SELECT * 
                                        FROM prop_transactions
                                        JOIN (
                                            
                                                SELECT wf_roleusermaps.user_id as role_user_id
                                                FROM wf_roles
                                                JOIN wf_roleusermaps ON wf_roleusermaps.wf_role_id = wf_roles.id 
                                                    AND wf_roleusermaps.is_suspended = FALSE
                                                JOIN wf_workflowrolemaps ON wf_workflowrolemaps.wf_role_id = wf_roleusermaps.wf_role_id
                                                    AND wf_workflowrolemaps.is_suspended = FALSE
                                                JOIN wf_workflows ON wf_workflows.id = wf_workflowrolemaps.workflow_id AND wf_workflows.is_suspended = FALSE 
                                                JOIN ulb_masters ON ulb_masters.id = wf_workflows.ulb_id
                                                WHERE wf_roles.is_suspended = FALSE 
                                                    AND wf_workflows.ulb_id = 2
                                                    AND wf_roles.id in (8,108)
                                                    AND wf_workflows.id in (3,4,5)
                                                GROUP BY wf_roleusermaps.user_id
                                                ORDER BY wf_roleusermaps.user_id
                                        ) collecter on prop_transactions.user_id  = collecter.role_user_id
                                    ) prop_transactions"), function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)"), "=", DB::RAW("UPPER(payment_modes.mode)"))
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("payment_modes.mode");
            if ($paymentMode) {
                $jsk = $jsk->where(DB::raw("upper(payment_modes.mode)"), $paymentMode);
            }
            $assestmentType = DB::table(
                DB::raw("(SELECT DISTINCT(UPPER(assessment_type)) AS mode 
                                    FROM (
                                            (
                                                select
                                                    distinct(
                                                        CASE WHEN assessment_type ILIKE '%MUTATION%' THEN 'MUTATION' 
                                                        ELSE UPPER(assessment_type)   
                                                        END 
                                                    ) AS assessment_type
                                                from prop_active_safs
                                            )
                                            union(
                                                select
                                                    distinct(
                                                        CASE WHEN assessment_type ILIKE '%MUTATION%' THEN 'MUTATION' 
                                                        ELSE UPPER(assessment_type)   
                                                        END 
                                                    ) AS assessment_type
                                                from prop_rejected_safs
                                            )
                                            union(
                                                    select
                                                        distinct(
                                                            CASE WHEN assessment_type ILIKE '%MUTATION%' THEN 'MUTATION' 
                                                            ELSE UPPER(assessment_type)   
                                                            END 
                                                        ) AS assessment_type
                                                    from prop_safs
                                            )
                                    )assesment_type
                                ) assesment_type")
            )
                ->select(
                    DB::raw("
                        CASE WHEN assesment_type.mode ILIKE '%MUTATION%' THEN 'MUTATION' 
                            ELSE assesment_type.mode 
                            END AS transaction_mode,
                        CASE WHEN assesment_type.mode ILIKE '%MUTATION%' THEN COUNT(DISTINCT(prop_transactions.saf_id)) 
                            ELSE COUNT(DISTINCT(prop_transactions.saf_id))
                            END AS saf_count,
                        CASE WHEN assesment_type.mode ILIKE '%MUTATION%' THEN COUNT(prop_transactions.id) 
                            ELSE COUNT(prop_transactions.id)
                            END AS tran_count, 
                        CASE WHEN assesment_type.mode ILIKE '%MUTATION%' THEN SUM(COALESCE(prop_transactions.amount,0)) 
                            ELSE SUM(COALESCE(prop_transactions.amount,0))
                            END AS amount
                        ")
                )
                ->LEFTJOIN(DB::RAW("(
                                        SELECT * 
                                        FROM(
                                            (
                                                SELECT prop_transactions.*, 
                                                    CASE WHEN prop_active_safs.assessment_type ILIKE '%MUTATION%' THEN 'MUTATION'
                                                        ELSE UPPER(prop_active_safs.assessment_type)
                                                        END AS assessment_type
                                                FROM prop_transactions
                                                JOIN prop_active_safs ON  prop_active_safs.id = prop_transactions.saf_id 
                                                WHERE prop_transactions.status in (1,2)
                                                    AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                                    " . ($userId ? " AND prop_transactions.user_id = $userId " : "") . "
                                                    " . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "") . "
                                            )
                                            UNION(
                                                SELECT prop_transactions.*,
                                                    CASE WHEN prop_rejected_safs.assessment_type ILIKE '%MUTATION%' THEN 'MUTATION'
                                                        ELSE UPPER(prop_rejected_safs.assessment_type)
                                                        END AS assessment_type 
                                                FROM prop_transactions
                                                JOIN prop_rejected_safs ON prop_transactions.id = prop_transactions.saf_id 
                                                WHERE prop_transactions.status in (1,2)
                                                    AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                                    " . ($userId ? " AND prop_transactions.user_id = $userId " : "") . "
                                                    " . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "") . "
                                            )
                                            UNION(
                                                SELECT prop_transactions.*,
                                                    CASE WHEN prop_safs.assessment_type ILIKE '%MUTATION%' THEN 'MUTATION'
                                                        ELSE UPPER(prop_safs.assessment_type)
                                                        END AS assessment_type  
                                                FROM prop_transactions
                                                JOIN prop_safs ON prop_safs.id = prop_transactions.saf_id 
                                                WHERE prop_transactions.status in (1,2)
                                                    AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                                    " . ($userId ? " AND prop_transactions.user_id = $userId " : "") . "
                                                    " . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "") . "
                                            )

                                        )prop_transactions
                                    ) prop_transactions"), function ($join) use ($fromDate, $uptoDate, $userId, $ulbId) {
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.assessment_type)"), "=", DB::RAW("UPPER(assesment_type.mode)"))
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status", [1, 2])
                        ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
                    if ($userId) {
                        $sub = $sub->WHERE("prop_transactions.user_id", $userId);
                    }
                    if ($ulbId) {
                        $sub = $sub->WHERE("prop_transactions.ulb_id", $ulbId);
                    }
                })
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->GROUPBY("assesment_type.mode");
            if ($paymentMode) {
                $assestmentType = $assestmentType->where(DB::raw("upper(prop_transactions.payment_mode)"), $paymentMode);
            }
            // dd($assestmentType->get());
            $collection = $collection->get();
            $refund     = $refund->get();
            $doorToDoor = $doorToDoor->get();
            $jsk        = $jsk->get();
            $assestmentType = $assestmentType->get();

            $totalCollection = $collection->sum("amount");
            $totalSaf = $collection->sum("saf_count");
            $totalTran = $collection->sum("tran_count");

            $totalCollectionRefund = $refund->sum("amount");
            $totalSafRefund = $refund->sum("saf_count");
            $totalTranRefund = $refund->sum("tran_count");

            $totalCollectionDoor = $doorToDoor->sum("amount");
            $totalSafDoor = $doorToDoor->sum("saf_count");
            $totalTranDoor = $doorToDoor->sum("tran_count");

            $totalCollectionJsk = $jsk->sum("amount");
            $totalSafJsk = $jsk->sum("saf_count");
            $totalTranJsk = $jsk->sum("tran_count");



            $collection[] = [
                "transaction_mode" => "Total Collection",
                "saf_count"    => $totalSaf,
                "tran_count"       => $totalTran,
                "amount"           => $totalCollection
            ];
            $funal["collection"] = $collection;
            $refund[] = [
                "transaction_mode" => "Total Refund",
                "saf_count"    => $totalSafRefund,
                "tran_count"       => $totalTranRefund,
                "amount"           => $totalCollectionRefund
            ];
            $funal["refund"] = $refund;
            $funal["netCollection"][] = [
                "transaction_mode" => "Net Collection",
                "saf_count"    => $totalSaf - $totalSafRefund,
                "tran_count"       => $totalTran - $totalTranRefund,
                "amount"           => $totalCollection - $totalCollectionRefund
            ];

            $doorToDoor[] = [
                "transaction_mode" => "Total Door To Door",
                "saf_count"    => $totalCollectionDoor,
                "tran_count"       => $totalSafDoor,
                "amount"           => $totalTranDoor
            ];
            $funal["doorToDoor"] = $doorToDoor;

            $jsk[] = [
                "transaction_mode" => "Total JSK",
                "saf_count"    => $totalCollectionJsk,
                "tran_count"       => $totalSafJsk,
                "amount"           => $totalTranJsk
            ];
            $funal["jsk"] = $jsk;
            $funal["assestment_type"] = $assestmentType;
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $funal, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function PropDCB(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $fiYear = getFY();
            $isGbsaf = null;
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            if ($request->isGbsaf) {
                $isGbsaf = $request->isGbsaf;
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
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $limit = $perPage;
            $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;


            $from = "
                FROM (
                    SELECT *
                    FROM prop_properties
                    WHERE prop_properties.ulb_id = $ulbId
                    ORDER BY id
                    limit $limit offset $offset
                  )prop_properties
                LEFT JOIN (
                    SELECT STRING_AGG(owner_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        prop_properties.id AS property_id
                    FROM prop_owners 
                    JOIN (
                        SELECT * 
                        FROM prop_properties
                        WHERE prop_properties.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )prop_properties ON prop_properties.id = prop_owners.property_id
                        AND prop_properties.ulb_id = $ulbId
                    WHERE prop_owners.status =1
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                    GROUP BY prop_properties.id
                )prop_owner_detail ON prop_owner_detail.property_id = prop_properties.id
                LEFT JOIN (
                    SELECT prop_demands.property_id,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(prop_demands.amount) AS total_demand
                    FROM prop_demands
                    JOIN (
                        SELECT * 
                        FROM prop_properties
                        WHERE prop_properties.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )prop_properties ON prop_properties.id = prop_demands.property_id
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.property_id    
                )demands ON demands.property_id = prop_properties.id
                LEFT JOIN (
                    SELECT prop_demands.property_id,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            cASe when prop_demands.due_date <'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(prop_demands.amount) AS total_collection
                    FROM prop_demands
                    JOIN (
                        SELECT * 
                        FROM prop_properties
                        WHERE prop_properties.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.property_id
                )collection ON collection.property_id = prop_properties.id
                LEFT JOIN ( 
                    SELECT prop_demands.property_id,
                    SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN (
                        SELECT * 
                        FROM prop_properties
                        WHERE prop_properties.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                    GROUP BY prop_demands.property_id
                )prev_collection ON prev_collection.property_id = prop_properties.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = prop_properties.ward_mstr_id
                WHERE  prop_properties.ulb_id = $ulbId  
                    " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "           
            ";
            $footerfrom = "
                FROM prop_properties
                LEFT JOIN (
                    SELECT STRING_AGG(owner_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        prop_properties.id AS property_id
                    FROM prop_owners 
                    JOIN prop_properties ON prop_properties.id = prop_owners.property_id
                        AND prop_properties.ulb_id = $ulbId
                    WHERE prop_owners.status =1
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                    GROUP BY prop_properties.id
                )prop_owner_detail ON prop_owner_detail.property_id = prop_properties.id
                LEFT JOIN (
                    SELECT prop_demands.property_id,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(prop_demands.amount) AS total_demand
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.property_id    
                )demands ON demands.property_id = prop_properties.id
                LEFT JOIN (
                    SELECT prop_demands.property_id,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            cASe when prop_demands.due_date <'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(prop_demands.amount) AS total_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.property_id
                )collection ON collection.property_id = prop_properties.id
                LEFT JOIN ( 
                    SELECT prop_demands.property_id,
                    SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                    GROUP BY prop_demands.property_id
                )prev_collection ON prev_collection.property_id = prop_properties.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = prop_properties.ward_mstr_id
                WHERE  prop_properties.ulb_id = $ulbId  
                    " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "           
            ";
            $select = "SELECT  prop_properties.id,
                            ulb_ward_masters.ward_name AS ward_no,
                            CONCAT('', prop_properties.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_properties.new_holding_no='' OR prop_properties.new_holding_no IS NULL THEN 'N/A' 
                                ELSE prop_properties.new_holding_no END
                            ) AS new_holding_no,
                            prop_owner_detail.owner_name,
                            prop_owner_detail.mobile_no,
                    
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ) AS arrear_demand,
                            COALESCE(demands.current_demand, 0::numeric) AS current_demand,   
                            COALESCE(prev_collection.total_prev_collection, 0::numeric) AS previous_collection,
                            
                            COALESCE(collection.arrear_collection, 0::numeric) AS arrear_collection,
                            COALESCE(collection.current_collection, 0::numeric) AS current_collection,
                    
                            (COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) )AS old_due,
                    
                            (COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)) AS current_due,
                    
                            (
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
                            ) AS outstanding                                 
            ";
            $footerselect = "SELECT
                        COUNT(prop_properties.id)AS total_prop,
                        COUNT(DISTINCT(ulb_ward_masters.ward_name)) AS total_ward,
                        SUM(
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            )
                        ) AS outstanding_at_begin,
                        SUM(COALESCE(demands.currend_demand, 0::numeric)) AS current_demand,
                
                        SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric)) AS prev_coll,
                        SUM(COALESCE(collection.arrear_collection, 0::numeric)) AS arrear_ollection,
                        SUM(COALESCE(collection.current_collection, 0::numeric)) AS current_collection,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                            )
                        )AS old_due,
                
                        SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric))) AS current_due,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.currend_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            )
                        ) AS outstanding               
            ";
            $data = DB::TABLE(DB::RAW("($select $from)AS prop"))->get();
            // $footer = DB::TABLE(DB::RAW("($footerselect $footerfrom)AS prop"))->get();
            $items = $data;
            $total = (collect(DB::SELECT("SELECT COUNT(*) AS total $footerfrom"))->first())->total ?? 0;
            $numberOfPages = ceil($total / $perPage);
            $list = [
                "perPage" => $perPage,
                "page" => $page,
                "items" => $items,
                // "footer"=>$footer,
                "total" => $total,
                "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function PropWardWiseDCB(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
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
            $from = "
                FROM ulb_ward_masters 
                LEFT JOIN(
                    SELECT prop_properties.ward_mstr_id,
                        COUNT(DISTINCT(prop_properties.id)) AS current_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(prop_demands.amount) AS total_demand
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_properties.ward_mstr_id    
                )demands ON demands.ward_mstr_id = ulb_ward_masters.id 
                LEFT JOIN (
                    SELECT prop_properties.ward_mstr_id,
                        COUNT(DISTINCT(prop_properties.id)) AS collection_from_no_of_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            cASe when prop_demands.due_date <'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(prop_demands.amount) AS total_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_properties.ward_mstr_id
                )collection ON collection.ward_mstr_id = ulb_ward_masters.id
                LEFT JOIN ( 
                    SELECT prop_properties.ward_mstr_id,
                    SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                    GROUP BY prop_properties.ward_mstr_id
                )prev_collection ON prev_collection.ward_mstr_id = ulb_ward_masters.id                 
                WHERE  ulb_ward_masters.ulb_id = $ulbId  
                    " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "           
            ";
            $select = "SELECT CASE WHEN ulb_ward_masters.old_ward_name IS NOT NULL THEN ulb_ward_masters.old_ward_name
                                ELSE  ulb_ward_masters.ward_name ::text
                                END AS ward_no, 
                            ulb_ward_masters.id, 
                            COALESCE(demands.current_hh, 0::numeric) AS current_hh,   
                            COALESCE(collection.collection_from_no_of_hh, 0::numeric) AS collection_from_hh,
                            COALESCE(
                                COALESCE(demands.current_hh, 0::numeric) 
                                - COALESCE(collection.collection_from_no_of_hh, 0::numeric), 0::numeric
                            ) AS balance_hh,                       
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ) AS arrear_demand,
                    
                            COALESCE(prev_collection.total_prev_collection, 0::numeric) AS previous_collection,
                            COALESCE(demands.current_demand, 0::numeric) AS current_demand,
                            COALESCE(collection.arrear_collection, 0::numeric) AS arrear_collection,
                            COALESCE(collection.current_collection, 0::numeric) AS current_collection,
                    
                            (COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) )AS old_due,
                    
                            (COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)) AS current_due,
                    
                            (
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
                            ) AS outstanding                                 
            ";
            $data = DB::select($select . $from);
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function PropFineRebate(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
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
            $data = PropProperty::SELECT(
                DB::RAW("
                        ulb_ward_masters.ward_name as ward_no,
                        CASE WHEN prop_properties.new_holding_no!='' 
                            THEN prop_properties.new_holding_no 
                            ELSE prop_properties.holding_no 
                            END AS holding_no,
                        prop_properties.id,
                        (COALESCE(demands.total_demand, 0)) AS total_demand,
                        (
                            COALESCE(prop_transactions.paid_amt,0)
                        )AS paid_amt,
                       (COALESCE(prop_transactions.demand_amt, 0))AS actual_demand_amt,
                       (COALESCE(fine_rebate.total_rebate, 0)) AS total_rebate,
                        (COALESCE(fine_rebate.first_qtr_rebate, 0)) AS first_qtr_rebate,
                        (COALESCE(fine_rebate.online_rebate, 0)) AS online_rebate,
                        (COALESCE(fine_rebate.jsk_rebate, 0)) AS jsk_rebate,        
                        (COALESCE(fine_rebate.special_rebate, 0)) AS special_rebate,
                        
                        (COALESCE(fine_rebate.total_fine, 0)) AS total_fine,
                        (COALESCE(fine_rebate.one_percent_penalty, 0)) AS one_percent_penalty,
                        (COALESCE(fine_rebate.late_assessment_penalty, 0)) AS late_assessment_penalty
                        ")
            )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_properties.ward_mstr_id")
                ->JOIN(
                    DB::RAW("
                                (
                                    SELECT 
                                        prop_demands.property_id, 
                                        SUM(
                                            COALESCE(
                                               prop_tran_dtls.total_demand 
                                                , 0
                                            )
                                        ) AS total_demand
                                    FROM prop_demands
                                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                                        AND  prop_tran_dtls.prop_demand_id NOTNULL
                                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                                        AND prop_transactions.property_id = prop_demands.property_id                                        
                                    WHERE prop_demands.status=1
                                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                                        AND prop_transactions.tran_date BETWEEN '$fromDate'AND '$uptoDate'
                                        " . ($ulbId ? " AND prop_transactions.ulb_id=$ulbId" : "") . "
                                    GROUP BY prop_demands.property_id 
                                )demands
                            "),
                    function ($join) {
                        $join->on("demands.property_id", "prop_properties.id");
                    }
                )
                ->JOIN(
                    DB::RAW("
                                    (
                                        SELECT
                                            prop_transactions.property_id AS property_id,
                                            SUM(COALESCE(prop_transactions.amount, 0)) AS paid_amt,
                                            SUM(COALESCE(prop_transactions.demand_amt, 0)) AS demand_amt        
                                        FROM prop_transactions
                                        WHERE 
                                            prop_transactions.property_id NOTNULL
                                            AND prop_transactions.status IN(1,2)
                                            AND prop_transactions.tran_date BETWEEN '$fromDate'AND '$uptoDate'
                                            " . ($ulbId ? " AND prop_transactions.ulb_id=$ulbId" : "") . "
                                        GROUP BY prop_transactions.property_id
                                    )prop_transactions
                                "),
                    function ($join) {
                        $join->on("prop_transactions.property_id", "prop_properties.id");
                    }
                )
                ->LEFTJOIN(
                    DB::RAW("
                            (
                                SELECT
                                    prop_transactions.property_id, 
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=TRUE 
                                                THEN prop_penaltyrebates.amount ELSE 0 END), 0)
                                    ) AS total_rebate,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=TRUE 
                                                            AND prop_penaltyrebates.head_name ILIKE'%First Qtr%' 
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS first_qtr_rebate,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=TRUE 
                                                            AND prop_penaltyrebates.head_name ILIKE'%JSK%' AND UPPER(prop_transactions.payment_mode)='ONLINE'
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS online_rebate,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=TRUE 
                                                            AND (prop_penaltyrebates.head_name ILIKE'%JSK%' OR prop_penaltyrebates.head_name ILIKE'%ONLINE%') AND UPPER(prop_transactions.payment_mode)!='ONLINE'
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS jsk_rebate,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=TRUE 
                                                            AND prop_penaltyrebates.head_name ILIKE'%Special Rebate%' 
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS special_rebate,

                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=FALSE THEN prop_penaltyrebates.amount 
                                                ELSE 0 END), 0)
                                    ) AS total_fine,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=FALSE 
                                                            AND prop_penaltyrebates.head_name ILIKE'%Monthly Penalty%' 
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS one_percent_penalty,
                                    SUM(COALESCE((CASE WHEN prop_penaltyrebates.is_rebate=FALSE 
                                                            AND prop_penaltyrebates.head_name ILIKE'%Late Assessment%' 
                                                        THEN prop_penaltyrebates.amount 
                                                        ELSE 0 END), 0)
                                    ) AS late_assessment_penalty
                                FROM prop_penaltyrebates
                                JOIN prop_transactions ON prop_penaltyrebates.tran_id = prop_transactions.id       
                                WHERE 
                                    prop_transactions.property_id NOTNULL 
                                    AND prop_transactions.status IN(1,2)
                                    AND prop_transactions.tran_date BETWEEN '$fromDate'AND '$uptoDate'
                                    " . ($ulbId ? " AND prop_transactions.ulb_id=$ulbId" : "") . "
                                GROUP BY prop_transactions.property_id
                            )fine_rebate
                            "),
                    function ($join) {
                        $join->on("fine_rebate.property_id", "prop_properties.id");
                    }
                );
            if ($wardId) {
                $data = $data->WHERE("ulb_ward_masters.id", $wardId);
            }
            if ($ulbId) {

                $data = $data->WHERE("prop_properties.ulb_id", $ulbId);
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
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
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function PropDeactedList(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = null;
            $wardId = null;
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }

            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            $data = PropProperty::select(
                DB::RAW("
                            prop_properties.id,
                            prop_properties.pt_no,
                            ulb_ward_masters.ward_name AS ward_no,
                            CASE WHEN prop_properties.new_holding_no!='' 
                                THEN prop_properties.new_holding_no 
                                ELSE prop_properties.holding_no 
                                END AS holding_no ,
                            owners_details.owner_name,  
                            owners_details.mobile_no,
                            CONCAT(prop_properties.prop_address, 
                                    ', City - ', prop_properties.prop_city, 
                                    ', Pin Code - ', prop_properties.prop_pin_code
                                ) AS address,
                            prop_deactivation_requests.remarks,
                            prop_deactivation_requests.documents
                            ")
            )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_properties.ward_mstr_id")
                ->JOIN("prop_deactivation_requests", "prop_deactivation_requests.property_id", "prop_properties.id")
                ->LEFTJOIN(DB::raw("(
                        SELECT
                            property_id,
                            STRING_AGG(owner_name,',')as owner_name,
                            STRING_AGG(guardian_name,',')as guardian_name,
                            STRING_AGG(mobile_no::text,',')as mobile_no
                        FROM view_owners_details
                        GROUP BY property_id
                        )owners_details"), function ($join) {
                    $join->on("owners_details.property_id", "prop_properties.id");
                })
                ->WHERE("prop_properties.status", 0);
            if ($fromDate && $uptoDate) {

                $data = $data->WHEREBETWEEN("prop_deactivation_requests.approve_date", [$fromDate, $uptoDate]);
            }

            if ($wardId) {
                $data = $data->WHERE("ulb_ward_masters.id", $wardId);
            }
            if ($ulbId) {

                $data = $data->WHERE("prop_properties.ulb_id", $ulbId);
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
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
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Property Individual Demand Collection
     */
    public function propIndividualDemandCollection($request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        $perPage = $request->perPage ? $request->perPage : 10;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $limit = $perPage;
        $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;
        $wardMstrId = NULL;
        $ulbId = authUser()->ulb_id;

        if ($request->wardMstrId) {
            $wardMstrId = $request->wardMstrId;
        }

        try {
            $sql = "SELECT p.id,p.ward_mstr_id,ward_name,p.holding_no,p.new_holding_no,p.prop_address,pt_no,
                        owner_name,
                        mobile_no,
                    prop_demands.total_demand,prop_demands.collection_amount,prop_demands.balance_amount
        
                FROM prop_properties AS p
                left JOIN (
                    SELECT property_id,
                        STRING_AGG(owner_name,',')as owner_name,
                        STRING_AGG(mobile_no::text,',')as mobile_no
                    FROM prop_owners
                    WHERE status =1
                    GROUP BY property_id
                ) prop_owners ON prop_owners.property_id = p.id
                JOIN ulb_ward_masters AS w ON w.id = p.ward_mstr_id
                left JOIN (
                    SELECT property_id,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =1 THEN amount ELSE 0 END )AS collection_amount,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount
                    FROM prop_demands 
                    WHERE status =1 
                    GROUP BY property_id
                    -- limit $limit offset $offset
                )prop_demands ON prop_demands.property_id = p.id
                WHERE p.ulb_id = $ulbId
                " . ($wardMstrId ? " AND p.ward_mstr_id = $wardMstrId" : "") . "";
            // limit $limit offset $offset";

            $sql2 = "SELECT count(*) as total
                    FROM prop_properties AS p
                left JOIN (
                    SELECT property_id,
                        STRING_AGG(owner_name,',')as owner_name,
                        STRING_AGG(mobile_no::text,',')as mobile_no
                    FROM prop_owners
                    WHERE status =1
                    GROUP BY property_id
                ) prop_owners ON prop_owners.property_id = p.id
                JOIN ulb_ward_masters AS w ON w.id = p.ward_mstr_id
                left JOIN (
                    SELECT property_id,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =1 THEN amount ELSE 0 END )AS collection_amount,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount
                    FROM prop_demands 
                    WHERE status =1 
                    GROUP BY property_id                    
                )prop_demands ON prop_demands.property_id = p.id
                WHERE  p.ulb_id = $ulbId
                " . ($wardMstrId ? " AND p.ward_mstr_id = $wardMstrId" : "") . "
               ";

            // return $list = DB::TABLE(DB::RAW("($sql )AS prop"))->get();

            $list = DB::table(DB::RAW("($sql )AS prop"))->paginate($request->perPage ?? 5);

            // return collect($list)->paginate($request->perPage ?? 10);
            // $items = $data;

            // $total = (collect(DB::SELECT($sql2))->first())->total ?? 0;
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            $queryRunTime = (collect(DB::getQueryLog($sql, $sql2))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | GBSAF Individual Demand Collection
     */
    public function gbsafIndividualDemandCollection($request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        $perPage = $request->perPage ? $request->perPage : 10;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $limit = $perPage;
        $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;
        $wardMstrId = NULL;
        $ulbId = authUser()->ulb_id;

        if ($request->wardMstrId) {
            $wardMstrId = $request->wardMstrId;
        }
        try {
            $sql = "SELECT p.id,p.ward_mstr_id,ward_name,saf_no,p.prop_address,gb_office_name,
            total_demand,collection_amount,balance_amount
            
                FROM prop_active_safs AS p
                JOIN prop_active_safgbofficers AS gbo ON gbo.saf_id = p.id
                JOIN ulb_ward_masters AS w ON w.id = p.ward_mstr_id
                left JOIN (
                    SELECT saf_id,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =1 THEN amount ELSE 0 END )AS collection_amount,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount
                    FROM prop_safs_demands 
                    WHERE status =1 
                    GROUP BY saf_id
            )prop_safs_demands ON prop_safs_demands.saf_id = p.id
                where is_gb_saf =true
                AND p.ulb_id = $ulbId
            " . ($wardMstrId ? " AND p.ward_mstr_id = $wardMstrId" : "") . "";
            // limit $limit offset $offset";

            $sql2 = "SELECT count(*) as total
            
                FROM prop_active_safs AS p
                JOIN prop_active_safgbofficers AS gbo ON gbo.saf_id = p.id
                JOIN ulb_ward_masters AS w ON w.id = p.ward_mstr_id
                left JOIN (
                    SELECT saf_id,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =1 THEN amount ELSE 0 END )AS collection_amount,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount
                    FROM prop_safs_demands 
                    WHERE status =1 
                    GROUP BY saf_id
            )prop_safs_demands ON prop_safs_demands.saf_id = p.id
                where is_gb_saf =true
                AND p.ulb_id = $ulbId
            " . ($wardMstrId ? " AND p.ward_mstr_id = $wardMstrId" : "") . "
            ";

            $list = DB::TABLE(DB::RAW("($sql )AS prop"))->paginate($request->perPage ?? 5);
            // $items = $data;

            // $total = (collect(DB::SELECT($sql2))->first())->total ?? 0;
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            $queryRunTime = (collect(DB::getQueryLog($sql, $sql2))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Not paid from 2019-2017
     */
    public function notPaidFrom2016($request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        $perPage = $request->perPage ? $request->perPage : 10;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $limit = $perPage;
        $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;
        $wardMstrId = NULL;
        $ulbId = authUser()->ulb_id;

        if ($request->wardMstrId) {
            $wardMstrId = $request->wardMstrId;
        }
        try {
            $sql = "SELECT prop_demands.property_id,prop_demands.ward_mstr_id,holding_no,new_holding_no,
                        owner_name,mobile_no,prop_address,ward_name,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount
                    FROM prop_demands 
                    JOIN (
                        SELECT property_id,
                        STRING_AGG(owner_name,',')as owner_name,
                        STRING_AGG(mobile_no::text,',')as mobile_no
                            FROM prop_owners
                            WHERE status =1
                            GROUP BY property_id 
                        ) AS o ON o.property_id = prop_demands.property_id
            
                    JOIN prop_properties on prop_properties.id = prop_demands.property_id
                    JOIN ulb_ward_masters ON ulb_ward_masters.id = prop_properties.ward_mstr_id
                    WHERE prop_demands.status =1 
                    " . ($wardMstrId ? " AND prop_demands.ward_mstr_id = $wardMstrId" : "") . "
                    AND fyear > '2016-2017'
                    AND paid_status = 0
                    AND prop_demands.ulb_id = $ulbId
                    GROUP BY prop_demands.property_id,holding_no,new_holding_no,owner_name,mobile_no,
                             prop_address,prop_demands.ward_mstr_id,ward_name
                    order by prop_demands.property_id";
            // limit $limit offset $offset";

            $sql2 = "SELECT count(DISTINCT prop_demands.property_id) as total
                        FROM prop_demands 
                        JOIN (
                            SELECT property_id,
                            STRING_AGG(owner_name,',')as owner_name,
                            STRING_AGG(mobile_no::text,',')as mobile_no
                                FROM prop_owners
                                WHERE status =1
                                GROUP BY property_id 
                            ) AS o ON o.property_id = prop_demands.property_id
                        
                            JOIN prop_properties on prop_properties.id = prop_demands.property_id
                        WHERE prop_demands.status =1 
                        " . ($wardMstrId ? " AND prop_demands.ward_mstr_id = $wardMstrId" : "") . "
                        AND fyear > '2016-2017'
                        AND paid_status = 0
            ";

            $list = DB::TABLE(DB::RAW("($sql )AS prop"))->paginate($request->perPage ?? 5);
            // $items = $data;

            // $total = (collect(DB::SELECT($sql2))->first())->total ?? 0;
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            $queryRunTime = (collect(DB::getQueryLog($sql, $sql2))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Paid Previous Year but not Current Year
     */
    public function previousYearPaidButnotCurrentYear($request)
    {
        try {
            $metaData = collect($request->metaData)->all();
            list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $limit = $perPage;
            $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;
            $wardMstrId = NULL;
            $ulbId = authUser()->ulb_id;

            $currentYear = Carbon::now()->year;
            $financialYearStart = $currentYear;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }
            $currentFinancialYear = $financialYearStart . '-' . ($financialYearStart + 1);
            $previousFinancialYear =  ($financialYearStart - 1) . '-' . ($financialYearStart);

            if ($request->wardMstrId) {
                $wardMstrId = $request->wardMstrId;
            }

            $sql = "SELECT prop_demands.property_id,new_holding_no,new_holding_no,pt_no,prop_address,ward_name,owner_name,mobile_no,
                        SUM (amount) AS total_demand,
                        SUM(CASE WHEN paid_status =0 THEN amount ELSE 0 END )AS balance_amount,
                        SUM(CASE WHEN paid_status =1 THEN amount ELSE 0 END )AS paid_amount
                    FROM prop_demands
                    JOIN (
                        SELECT property_id,
                        STRING_AGG(owner_name,',')as owner_name,
                        STRING_AGG(mobile_no::text,',')as mobile_no
                            FROM prop_owners
                            WHERE status =1
                            GROUP BY property_id 
                        ) AS ow ON ow.property_id = prop_demands.property_id
        
                    left join(
                            select property_id
                                from prop_demands
                                WHERE prop_demands.status =1
                                AND fyear = '$previousFinancialYear'
                                AND paid_status = 1
                            GROUP BY property_id 
                            ) AS o ON o.property_id = prop_demands.property_id 
                            join prop_properties on prop_properties.id=prop_demands.property_id 
                            join ulb_ward_masters on ulb_ward_masters.id = prop_demands.ward_mstr_id
                        WHERE prop_demands.status =1 
                        " . ($wardMstrId ? " AND prop_demands.ward_mstr_id = $wardMstrId" : "") . "
                        AND fyear = '$currentFinancialYear' 
                        AND paid_status = 0
                        GROUP BY prop_demands.property_id,new_holding_no,new_holding_no,pt_no,prop_address,ward_name,owner_name,mobile_no
                   ";
            // limit $limit offset $offset";

            $sql2 = "SELECT count(distinct prop_demands.property_id) as total
                        FROM prop_demands
                        JOIN (
                            SELECT property_id,
                            STRING_AGG(owner_name,',')as owner_name,
                            STRING_AGG(mobile_no::text,',')as mobile_no
                                FROM prop_owners
                                WHERE status =1
                                GROUP BY property_id 
                            ) AS ow ON ow.property_id = prop_demands.property_id
            
                    left join(
                            select property_id
                                from prop_demands
                                WHERE prop_demands.status =1
                                AND fyear = '2021-2022'
                                AND paid_status = 1
                            GROUP BY property_id 
                            ) AS o ON o.property_id = prop_demands.property_id 
                            join prop_properties on prop_properties.id=prop_demands.property_id 
                            join ulb_ward_masters on ulb_ward_masters.id = prop_demands.ward_mstr_id
                        WHERE prop_demands.status =1 
                        " . ($wardMstrId ? " AND prop_demands.ward_mstr_id = $wardMstrId" : "") . "
                        AND fyear = '2022-2023' 
                        AND paid_status = 0";

            $list = DB::TABLE(DB::RAW("($sql )AS prop"))->paginate($request->perPage ?? 5);
            // $items = $data;

            // $total = (collect(DB::SELECT($sql2))->first()->total) ?? 0;
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            $queryRunTime = (collect(DB::getQueryLog($sql, $sql2))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
}
