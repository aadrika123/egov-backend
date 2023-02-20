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
    public function collectionReport(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if($request->fromDate)
            {
                $fromDate = $request->fromDate;
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            if($request->wardId)
            {
                $wardId = $request->wardId;
            }
            if($request->userId)
            {
                $userId = $request->userId;
            }
            if($request->paymentMode)
            {
                $paymentMode = $request->paymentMode;
            }
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
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
                ->JOIN("prop_properties","prop_properties.id","prop_transactions.property_id")
                ->JOIN(DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, prop_owners.property_id 
                        FROM prop_owners 
                        JOIN prop_transactions on prop_transactions.property_id = prop_owners.property_id 
                        WHERE prop_transactions.property_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        ".
                        ($userId?" AND prop_transactions.user_id = $userId ":"")
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper($paymentMode) ":"")
                        .($ulbId?" AND prop_transactions.ulb_id = $ulbId": "")
                        ."
                        GROUP BY prop_owners.property_id
                        ) AS prop_owner_detail
                        "),function($join){
                            $join->on("prop_owner_detail.property_id","=","prop_transactions.property_id");
                        }
                )
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_properties.ward_mstr_id")
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls","prop_cheque_dtls.transaction_id","prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.property_id")
                ->WHEREIN("prop_transactions.status",[1,2])
                ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                if($wardId)
                {
                    $data=$data->where("ulb_ward_masters.id",$wardId);
                }
                if($userId)
                {
                    $data=$data->where("prop_transactions.user_id",$userId);
                }
                if($paymentMode)
                {
                    $data=$data->where(DB::row("prop_transactions.upper(payment_mode)"),$paymentMode);
                }
                if($ulbId)
                {
                    $data=$data->where("prop_transactions.ulb_id",$ulbId);
                }
                // $data = $data->limit(200)->get();
                $perPage = $request->perPage ? $request->perPage : 10;
                $page = $request->page && $request->page > 0 ? $request->page : 1;
                $paginator = $data->paginate($perPage);
                $items = $paginator->items();
                $total = $paginator->total();
                $numberOfPages = ceil($total/$perPage);                
                $list=[
                    "perPage"=>$perPage,
                    "page"=>$page,
                    "items"=>$items,
                    "total"=>$total,
                    "numberOfPages"=>$numberOfPages
                ];
                $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
                return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
    public function safCollection(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if($request->fromDate)
            {
                $fromDate = $request->fromDate;
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            if($request->wardId)
            {
                $wardId = $request->wardId;
            }
            if($request->userId)
            {
                $userId = $request->userId;
            }
            if($request->paymentMode)
            {
                $paymentMode = $request->paymentMode;
            }
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $activSaf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
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
                ->JOIN("prop_active_safs","prop_active_safs.id","prop_transactions.saf_id")
                ->JOIN(DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_active_safs_owners.saf_id 
                        FROM prop_active_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_active_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        ".
                        ($userId?" AND prop_transactions.user_id = $userId ":"")
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper($paymentMode) ":"")
                        .($ulbId?" AND prop_transactions.ulb_id = $ulbId": "")
                        ."
                        GROUP BY prop_active_safs_owners.saf_id 
                        ) AS owner_detail
                        "),function($join){
                            $join->on("owner_detail.saf_id","=","prop_transactions.saf_id");
                        }
                )
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_active_safs.ward_mstr_id")
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls","prop_cheque_dtls.transaction_id","prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status",[1,2])
                ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);

            $rejectedSaf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
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
                ->JOIN("prop_rejected_safs","prop_rejected_safs.id","prop_transactions.saf_id")
                ->JOIN(DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_rejected_safs_owners.saf_id 
                        FROM prop_rejected_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_rejected_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        ".
                        ($userId?" AND prop_transactions.user_id = $userId ":"")
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper($paymentMode) ":"")
                        .($ulbId?" AND prop_transactions.ulb_id = $ulbId": "")
                        ."
                        GROUP BY prop_rejected_safs_owners.saf_id 
                        ) AS owner_detail
                        "),function($join){
                            $join->on("owner_detail.saf_id","=","prop_transactions.saf_id");
                        }
                )
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_rejected_safs.ward_mstr_id")
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls","prop_cheque_dtls.transaction_id","prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status",[1,2])
                ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);

            $saf = PropTransaction::select(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
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
                ->JOIN("prop_safs","prop_safs.id","prop_transactions.saf_id")
                ->JOIN(DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            prop_safs_owners.saf_id 
                        FROM prop_safs_owners 
                        JOIN prop_transactions on prop_transactions.saf_id = prop_safs_owners.saf_id 
                        WHERE prop_transactions.saf_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        ".
                        ($userId?" AND prop_transactions.user_id = $userId ":"")
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper($paymentMode) ":"")
                        .($ulbId?" AND prop_transactions.ulb_id = $ulbId": "")
                        ."
                        GROUP BY prop_safs_owners.saf_id 
                        ) AS owner_detail
                        "),function($join){
                            $join->on("owner_detail.saf_id","=","prop_transactions.saf_id");
                        }
                )
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_safs.ward_mstr_id")
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls","prop_cheque_dtls.transaction_id","prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.saf_id")
                ->WHEREIN("prop_transactions.status",[1,2])
                ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);

            if($wardId)
            {
                $activSaf = $activSaf->where("ulb_ward_masters.id",$wardId);
                $rejectedSaf = $rejectedSaf->where("ulb_ward_masters.id",$wardId);
                $saf = $saf->where("ulb_ward_masters.id",$wardId);
            }
            if($userId)
            {
                $activSaf=$activSaf->where("prop_transactions.user_id",$userId);
                $rejectedSaf=$rejectedSaf->where("prop_transactions.user_id",$userId);
                $saf=$saf->where("prop_transactions.user_id",$userId);
            }
            if($paymentMode)
            {
                $activSaf=$activSaf->where(DB::row("prop_transactions.upper(payment_mode)"),$paymentMode);
                $rejectedSaf=$rejectedSaf->where(DB::row("prop_transactions.upper(payment_mode)"),$paymentMode);
                $saf=$saf->where(DB::row("prop_transactions.upper(payment_mode)"),$paymentMode);
            }
            if($ulbId)
            {
                $activSaf=$activSaf->where("prop_transactions.ulb_id",$ulbId);
                $rejectedSaf=$rejectedSaf->where("prop_transactions.ulb_id",$ulbId);
                $saf=$saf->where("prop_transactions.ulb_id",$ulbId);
            }
            
            $data = $activSaf->union($rejectedSaf)->union($saf);
            // dd(DB::getQueryLog());
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total/$perPage);                
            $list=[
                "perPage"=>$perPage,
                "page"=>$page,
                "items"=>$items,
                "total"=>$total,
                "numberOfPages"=>$numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function safPropIndividualDemandAndCollection(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $key = null;
            $fiYear = getFY();
            if($request->fiYear)
            {
                $fiYear = $request->fiYear;                
            }
            list($fromYear,$toYear)=explode("-",$fiYear);
            if($toYear-$fromYear !=1)
            {
                throw new Exception("Enter Valide Financial Year");
            }
            if($request->key)
            {
                $key = $request->key;
            }
            if($request->wardId)
            {
                $wardId = $request->wardId;
            }
            if($request->ulbId)
            {
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
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_properties.ward_mstr_id")
                ->LEFTJOIN("prop_safs","prop_safs.id","prop_properties.saf_id")
                ->JOIN(DB::RAW("(
                        SELECT 
                            prop_owners.property_id, 
                            STRING_AGG(prop_owners.owner_name, ',') AS owner_name, 
                            STRING_AGG(prop_owners.mobile_no::TEXT, ',') AS mobile_no 
                        FROM prop_owners
                        WHERE prop_owners.status=1
                        GROUP BY prop_owners.property_id
                        ) AS owner_detail
                        "),function($join){
                            $join->on("owner_detail.property_id","=","prop_properties.id");
                        }
                )
                ->LEFTJOIN(DB::RAW("(
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
                        "),function($join){
                            $join->on("floor_details.property_id","=","prop_properties.id");
                        }
                )
                ->LEFTJOIN(DB::RAW("(
                    SELECT 
                        property_id, 
                        SUM(CASE WHEN fyear < '$fiYear' THEN amount ELSE 0 END) AS arrear_demand,
                        SUM(CASE WHEN fyear = '$fiYear' THEN amount ELSE 0 END) AS current_demand
                    FROM prop_demands 
                    WHERE status=1 AND paid_status IN (0,1)  
                    GROUP BY property_id
                    )AS demands
                    "),function($join){
                        $join->on("demands.property_id","=","prop_properties.id");
                    }
                )
                ->LEFTJOIN(DB::RAW("(
                    SELECT 
                        property_id, 
                        SUM(CASE WHEN fyear < '$fiYear' THEN amount ELSE 0 END) AS arrear_collection,
                        SUM(CASE WHEN fyear = '$fiYear' THEN amount ELSE 0 END) AS current_collection
                    FROM prop_demands 
                    WHERE status=1 AND paid_status=1 
                    GROUP BY property_id
                    )AS collection
                    "),function($join){
                        $join->on("collection.property_id","=","prop_properties.id");
                    }
                )
                ->LEFTJOIN(DB::RAW("(
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
                    "),function($join){
                        $join->on("tbl_penalty.property_id","=","prop_properties.id");
                    }
                )
                ->LEFTJOIN(DB::RAW("(
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
                    "),function($join){
                        $join->on("tbl_rebate.property_id","=","prop_properties.id");
                    }
                )
                ->WHERE("prop_properties.status",1)
                ->WHERE(function($where)use ($key){
                    $where->ORWHERE('prop_properties.holding_no', 'ILIKE', '%' . $key . '%')
                    ->ORWHERE('prop_properties.new_holding_no', 'ILIKE', '%' . $key . '%')
                    ->ORWHERE('prop_safs.saf_no', 'ILIKE', '%' . $key . '%')
                    ->ORWHERE('owner_detail.owner_name', 'ILIKE', '%' . $key . '%')
                    ->ORWHERE('owner_detail.mobile_no', 'ILIKE', '%' . $key . '%')
                    ->ORWHERE('prop_properties.prop_address', 'ILIKE', '%' . $key . '%');
                });
                if($wardId)
                {
                    $data=$data->where("ulb_ward_masters.id",$wardId);
                }                
                if($ulbId)
                {
                    $data=$data->where("prop_properties.ulb_id",$ulbId);
                }         
                $perPage = $request->perPage ? $request->perPage : 10;
                $page = $request->page && $request->page > 0 ? $request->page : 1;
                $paginator = $data->paginate($perPage);
                $items = $paginator->items();
                $total = $paginator->total();
                $numberOfPages = ceil($total/$perPage);                
                $list=[
                    "perPage"=>$perPage,
                    "page"=>$page,
                    "items"=>$items,
                    "total"=>$total,
                    "numberOfPages"=>$numberOfPages
                ];
                $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
                return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function levelwisependingform(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = WfRole::SELECT("wf_roles.id","wf_roles.role_name",
                    DB::RAW("COUNT(prop_active_safs.id) AS total")
                    )
                ->JOIN(DB::RAW("(
                                        SELECT distinct(wf_role_id) as wf_role_id
                                        FROM wf_workflowrolemaps 
                                        WHERE  wf_workflowrolemaps.is_suspended = false AND (forward_role_id IS NOT NULL OR backward_role_id IS NOT NULL)
                                            AND workflow_id IN(3,4,5) 
                                        GROUP BY wf_role_id 
                                ) AS roles
                    "),function($join){
                        $join->on("wf_roles.id","roles.wf_role_id");
                    })
                ->LEFTJOIN("prop_active_safs",function($join) use($ulbId){
                    $join->ON("prop_active_safs.current_role","roles.wf_role_id")
                    ->WHERE("prop_active_safs.ulb_id",$ulbId)
                    ->WHERE(function($where){
                        $where->ORWHERE("prop_active_safs.payment_status","<>",0)
                        ->ORWHERENOTNULL("prop_active_safs.payment_status");
                    });
                })
                ->GROUPBY(["wf_roles.id","wf_roles.role_name"])
                ->UNION(
                    PropActiveSaf::SELECT(
                        DB::RAW("8 AS id, 'JSK' AS role_name,
                                COUNT(prop_active_safs.id)")
                    )
                    ->WHERE("prop_active_safs.ulb_id",$ulbId)
                    ->WHERENOTNULL("prop_active_safs.user_id")
                    ->WHERE(function($where){
                        $where->WHERE("prop_active_safs.payment_status","=",0)
                            ->ORWHERENULL("prop_active_safs.payment_status");
                    })
                )
                ->GET();
                $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
                return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function levelformdetail(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;            
            $roleId = $roleId2 = $userId = null;
            $mWardPermission = collect([]);
            
            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->roleId)
            {
                $roleId = $request->roleId;
            }
            if($request->userId)
            {
                $userId = $request->userId; 
                $roleId2 = ($this->_common->getUserRoll($userId,$ulbId,$safWorkFlow))->role_id??0;
            }
            if(($request->roleId && $request->userId) && ($roleId!=$roleId2))
            {
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2?$roleId2:$roleId;
            if(!in_array($roleId,[11,8]))
            {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }
           
            $mWardIds = $mWardPermission->implode("id",",");
            $mWardIds = explode(',',($mWardIds?$mWardIds:"0"));
            // DB::enableQueryLog();
            $data = PropActiveSaf::SELECT(
                    DB::RAW("wf_roles.id AS role_id, wf_roles.role_name,
                    prop_active_safs.id, prop_active_safs.saf_no, prop_active_safs.prop_address,
                    ward_name as ward_no, 
                    owner.owner_name, owner.mobile_no")
            )
            ->JOIN("ulb_ward_masters","ulb_ward_masters.id","prop_active_safs.ward_mstr_id")
            ->LEFTJOIN(DB::RAW("( 
                                SELECT DISTINCT(prop_active_safs_owners.saf_id) AS saf_id,STRING_AGG(owner_name,',') AS owner_name, 
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no
                                FROM prop_active_safs_owners
                                JOIN prop_active_safs ON prop_active_safs.id = prop_active_safs_owners.saf_id
                                WHERE prop_active_safs_owners.status = 1 
                                    AND prop_active_safs.ulb_id = $ulbId
                                GROUP BY prop_active_safs_owners.saf_id
                                ) AS owner
                                "),function($join){
                                    $join->on("owner.saf_id","=","prop_active_safs.id");
                                });
            if($roleId==8)
            {
                $data = $data->LEFTJOIN("wf_roles","wf_roles.id","prop_active_safs.current_role")
                        ->WHERENOTNULL("prop_active_safs.user_id")
                        ->WHERE(function($where){
                            $where->WHERE("prop_active_safs.payment_status","=",0)
                            ->ORWHERENULL("prop_active_safs.payment_status");
                        });
            }
            else
            {
                $data = $data->JOIN("wf_roles","wf_roles.id","prop_active_safs.current_role") 
                        ->WHERE("wf_roles.id",$roleId);
            }
            $data = $data->WHERE("prop_active_safs.ulb_id",$ulbId);
            if(!in_array($roleId,[11,8]) && $userId)
            {
                $data = $data->WHEREIN("prop_active_safs.ward_mstr_id",$mWardIds);
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total/$perPage);                
            $list=[
                "perPage"=>$perPage,
                "page"=>$page,
                "items"=>$items,
                "total"=>$total,
                "numberOfPages"=>$numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function userWiseWardWireLevelPending(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;            
            $roleId = $roleId2 = $userId = null;
            $mWardPermission = collect([]);
            
            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->roleId)
            {
                $roleId = $request->roleId;
            }
            if($request->userId)
            {
                $userId = $request->userId; 
                $roleId2 = ($this->_common->getUserRoll($userId,$ulbId,$safWorkFlow))->role_id??0;
            }
            if(($request->roleId && $request->userId) && ($roleId!=$roleId2))
            {
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2?$roleId2:$roleId;
            if(!in_array($roleId,[11,8]))
            {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }
           
            $mWardIds = $mWardPermission->implode("id",",");
            $mWardIds = explode(',',($mWardIds?$mWardIds:"0"));
            // DB::enableQueryLog();
            $data = UlbWardMaster::SELECT(
                    DB::RAW(" DISTINCT(ward_name) as ward_no, COUNT(prop_active_safs.id) AS total")
            )
            ->LEFTJOIN("prop_active_safs","ulb_ward_masters.id","prop_active_safs.ward_mstr_id");
            if($roleId==8)
            {
                $data = $data->LEFTJOIN("wf_roles","wf_roles.id","prop_active_safs.current_role")
                        ->WHERENOTNULL("prop_active_safs.user_id")
                        ->WHERE(function($where){
                            $where->WHERE("prop_active_safs.payment_status","=",0)
                            ->ORWHERENULL("prop_active_safs.payment_status");
                        });
            }
            else
            {
                $data = $data->JOIN("wf_roles","wf_roles.id","prop_active_safs.current_role") 
                        ->WHERE("wf_roles.id",$roleId);
            }
            if(!in_array($roleId,[11,8]) && $userId)
            {
                $data = $data->WHEREIN("prop_active_safs.ward_mstr_id",$mWardIds);
            }
            $data = $data->WHERE("prop_active_safs.ulb_id",$ulbId);
            $data = $data->groupBy(["ward_name"]);

            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total/$perPage);                
            $list=[
                "perPage"=>$perPage,
                "page"=>$page,
                "items"=>$items,
                "total"=>$total,
                "numberOfPages"=>$numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }


    public function safSamFamGeotagging(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;  
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->fromDate)
            {
                $fromDate = $request->fromDate;
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            if($request->wardId)
            {
                $wardId = $request->wardId;
            }
            $where = " WHERE status = 1 AND  ulb_id = $ulbId
                        AND created_at::date BETWEEN '$fromDate' AND '$uptoDate'";
            if($wardId)
            {
                $where .= " AND ward_mstr_id = $wardId ";
            }            
            $sql ="
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

            return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        } 
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
}