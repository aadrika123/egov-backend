<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\Property\PropProperty;
use App\Models\Property\PropTransaction;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
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
                            ROW_NUMBER () OVER (ORDER BY prop_transactions.tran_date asc) AS s_no,
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
                $data = $data->limit(200)->get();
                return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
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

            DB::enableQueryLog();
            $activSaf = PropTransaction::select(
                DB::raw("
                            ROW_NUMBER () OVER (ORDER BY prop_transactions.tran_date asc) AS s_no,
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
                            ROW_NUMBER () OVER (ORDER BY prop_transactions.tran_date asc) AS s_no,
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
                            ROW_NUMBER () OVER (ORDER BY prop_transactions.tran_date asc) AS s_no,
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
                
                $data = $activSaf->union($rejectedSaf)->union($saf)
                        ->limit(200)
                        ->get();
                // dd(DB::getQueryLog());
                return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function safPropIndividualDemandAndCollecton(Request $request)
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
                $data = $data->limit(200)->get();
                return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
}