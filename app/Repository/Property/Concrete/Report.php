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
                ->JOIN("prop_properties","prop_properties.id","prop_transactions.property_id")
                ->JOIN(DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, prop_owners.property_id 
                        FROM prop_owners 
                        JOIN prop_transactions on prop_transactions.property_id = prop_owners.property_id 
                        WHERE prop_transactions.property_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        ".
                        ($userId?" AND prop_transactions.user_id = $userId ":"")
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper('$paymentMode') ":"")
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
                    $data=$data->where(DB::raw("upper(prop_transactions.payment_mode)"),$paymentMode);
                }
                if($ulbId)
                {
                    $data=$data->where("prop_transactions.ulb_id",$ulbId);
                }
                $data2 = $data;
                $totalHolding = $data2->count("prop_properties.id");
                $totalAmount = $data2->sum("prop_transactions.amount");
                $perPage = $request->perPage ? $request->perPage : 10;
                $page = $request->page && $request->page > 0 ? $request->page : 1;
                $paginator = $data->paginate($perPage);
                $items = $paginator->items();
                $total = $paginator->total();
                $numberOfPages = ceil($total/$perPage);                
                $list=[
                    "perPage"=>$perPage,
                    "page"=>$page,                    
                    "totalHolding"=>$totalHolding,
                    "totalAmount"=>$totalAmount,    
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
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper('$paymentMode') ":"")
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
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper('$paymentMode') ":"")
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
                        .($paymentMode?" AND upper(prop_transactions.payment_mode) = upper('$paymentMode') ":"")
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
            $data2 = $data;
            $totalSaf = $data2->count("id");
            $totalAmount = $data2->sum("amount");
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total/$perPage);                
            $list=[
                "perPage"=>$perPage,
                "page"=>$page,
                "totalSaf"=>$totalSaf,
                "totalAmount"=>$totalAmount,  
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

    public function levelUserPending(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;            
            $roleId = $roleId2 = $userId = null;
            $joins = "join";
            
            $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->roleId)
            {
                $roleId = $request->roleId;
            }            
            if(($request->roleId && $request->userId) && ($roleId!=$roleId2))
            {
                throw new Exception("Invalid RoleId Pass");
            }
            if(in_array($roleId,[11,8]))
            {
                $joins="leftjoin";
            }
           
            // DB::enableQueryLog();
            $data = PropActiveSaf::SELECT(
                    DB::RAW(
                        "count(prop_active_safs.id),
                    users_role.user_id ,
                    users_role.user_name,
                    users_role.wf_role_id as role_id,
                    users_role.role_name")
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
                    "),function($join)use($joins){
                        if($joins=="join")
                        {
                            $join->on("users_role.wf_role_id","=","prop_active_safs.current_role")
                            ->where("prop_active_safs.ward_mstr_id",DB::raw("ANY (ward_ids::int[])"));
                        }
                        else
                        {
                            $join->on(DB::raw("1"),DB::raw("1"));
                        }
                    }
            )
            ->WHERE("prop_active_safs.ulb_id",$ulbId)
            ->groupBy(["users_role.user_id" ,"users_role.user_name","users_role.wf_role_id" ,"users_role.role_name"]);
            
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
            dd($e->getMessage(),$e->getLine(),$e->getFile());
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

    public function PropPaymentModeWiseSummery(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;  
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
            $paymentMode = null;
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
            if($request->userId)
            {
                $userId = $request->userId;
            }
            if($request->paymentMode)
            {
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
                        "))
                ->LEFTJOIN("prop_transactions",function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode) "))
                    ->WHERENOTNULL("prop_transactions.property_id")
                    ->WHEREIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $collection=$collection->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
                ->LEFTJOIN("prop_transactions",function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode) "))
                    ->WHERENOTNULL("prop_transactions.property_id")
                    ->WHERENOTIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $refund=$refund->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
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
                                ) prop_transactions"),function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode)"))                    
                    ->WHERENOTNULL("prop_transactions.property_id")
                    ->WHEREIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $doorToDoor=$doorToDoor->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
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
                                    ) prop_transactions"),function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                        $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode)"))                    
                        ->WHERENOTNULL("prop_transactions.property_id")
                        ->WHEREIN("prop_transactions.status",[1,2])
                        ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                        if($userId)
                        {
                            $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                        }
                        if($ulbId)
                        {
                            $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                        }
                    })
                    ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                    ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $jsk=$jsk->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
            }

            $collection = $collection->get();
            $refund     = $refund->get();
            $doorToDoor =$doorToDoor->get();
            $jsk        =$jsk->get();

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

            $collection[]=["transaction_mode" =>"Total Collection",
                        "holding_count"    => $totalHolding,
                        "tran_count"       => $totalTran,
                        "amount"           => $totalCollection
                    ];
            $funal["collection"] = $collection;
            $refund[]=["transaction_mode" =>"Total Refund",
                    "holding_count"    => $totalHoldingRefund,
                    "tran_count"       => $totalTranRefund,
                    "amount"           => $totalCollectionRefund
                ];
            $funal["refund"] = $refund;            
            $funal["netCollection"][] = [
                                        "transaction_mode" =>"Net Collection",
                                        "holding_count"    => $totalHolding - $totalHoldingRefund,
                                        "tran_count"       => $totalTran - $totalTranRefund,
                                        "amount"           => $totalCollection - $totalCollectionRefund
                                    ];
            
            $doorToDoor[]=["transaction_mode" =>"Total Door To Door",
                    "holding_count"    => $totalCollectionDoor,
                    "tran_count"       => $totalHoldingDoor,
                    "amount"           => $totalTranDoor
                ];
            $funal["doorToDoor"] = $doorToDoor;

            $jsk[]=["transaction_mode" =>"Total JSK",
                    "holding_count"    => $totalCollectionJsk,
                    "tran_count"       => $totalHoldingJsk,
                    "amount"           => $totalTranJsk
                ];
            $funal["jsk"] = $jsk;
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$funal,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function SafPaymentModeWiseSummery(Request $request)
    {
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;  
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
            $paymentMode = null;
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
            if($request->userId)
            {
                $userId = $request->userId;
            }
            if($request->paymentMode)
            {
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
                        "))
                ->LEFTJOIN("prop_transactions",function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode) "))
                    ->WHERENOTNULL("prop_transactions.saf_id")
                    ->WHEREIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $collection=$collection->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
                ->LEFTJOIN("prop_transactions",function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode) "))
                    ->WHERENOTNULL("prop_transactions.saf_id")
                    ->WHERENOTIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $refund=$refund->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
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
                                ) prop_transactions"),function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                    $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode)"))                    
                    ->WHERENOTNULL("prop_transactions.saf_id")
                    ->WHEREIN("prop_transactions.status",[1,2])
                    ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                    if($userId)
                    {
                        $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                    }
                    if($ulbId)
                    {
                        $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                    }
                })
                ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $doorToDoor=$doorToDoor->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
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
                                    ) prop_transactions"),function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                        $sub = $join->on(DB::RAW("UPPER(prop_transactions.payment_mode)") ,"=",DB::RAW("UPPER(payment_modes.mode)"))                    
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status",[1,2])
                        ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                        if($userId)
                        {
                            $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                        }
                        if($ulbId)
                        {
                            $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                        }
                    })
                    ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                    ->GROUPBY("payment_modes.mode"); 
            if($paymentMode)
            {
                $jsk=$jsk->where(DB::raw("upper(payment_modes.mode)"),$paymentMode);
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
                        "))
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
                                                    ".($userId ? " AND prop_transactions.user_id = $userId " : "")."
                                                    ".($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "")."
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
                                                    ".($userId ? " AND prop_transactions.user_id = $userId " : "")."
                                                    ".($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "")."
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
                                                    ".($userId ? " AND prop_transactions.user_id = $userId " : "")."
                                                    ".($ulbId ? " AND prop_transactions.ulb_id = $ulbId " : "")."
                                            )

                                        )prop_transactions
                                    ) prop_transactions"),function($join)use($fromDate,$uptoDate,$userId,$ulbId){
                        $sub = $join->on(DB::RAW("UPPER(prop_transactions.assessment_type)") ,"=",DB::RAW("UPPER(assesment_type.mode)"))                    
                        ->WHERENOTNULL("prop_transactions.saf_id")
                        ->WHEREIN("prop_transactions.status",[1,2])
                        ->WHEREBETWEEN("prop_transactions.tran_date",[$fromDate,$uptoDate]);
                        if($userId)
                        {
                            $sub = $sub->WHERE("prop_transactions.user_id",$userId);
                        }
                        if($ulbId)
                        {
                            $sub = $sub->WHERE("prop_transactions.ulb_id",$ulbId);
                        }
                    })
                    ->LEFTJOIN("users","users.id","prop_transactions.user_id")                
                    ->GROUPBY("assesment_type.mode"); 
            if($paymentMode)
            {
                $assestmentType=$assestmentType->where(DB::raw("upper(prop_transactions.payment_mode)"),$paymentMode);
            }
            // dd($assestmentType->get());
            $collection = $collection->get();
            $refund     = $refund->get();
            $doorToDoor =$doorToDoor->get();
            $jsk        =$jsk->get();
            $assestmentType=$assestmentType->get();

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

           

            $collection[]=["transaction_mode" =>"Total Collection",
                        "saf_count"    => $totalSaf,
                        "tran_count"       => $totalTran,
                        "amount"           => $totalCollection
                    ];
            $funal["collection"] = $collection;
            $refund[]=["transaction_mode" =>"Total Refund",
                    "saf_count"    => $totalSafRefund,
                    "tran_count"       => $totalTranRefund,
                    "amount"           => $totalCollectionRefund
                ];
            $funal["refund"] = $refund;            
            $funal["netCollection"][] = [
                                        "transaction_mode" =>"Net Collection",
                                        "saf_count"    => $totalSaf - $totalSafRefund,
                                        "tran_count"       => $totalTran - $totalTranRefund,
                                        "amount"           => $totalCollection - $totalCollectionRefund
                                    ];
            
            $doorToDoor[]=["transaction_mode" =>"Total Door To Door",
                    "saf_count"    => $totalCollectionDoor,
                    "tran_count"       => $totalSafDoor,
                    "amount"           => $totalTranDoor
                ];
            $funal["doorToDoor"] = $doorToDoor;

            $jsk[]=["transaction_mode" =>"Total JSK",
                    "saf_count"    => $totalCollectionJsk,
                    "tran_count"       => $totalSafJsk,
                    "amount"           => $totalTranJsk
                ];
            $funal["jsk"] = $jsk;
            $funal["assestment_type"] = $assestmentType;
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$funal,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
}