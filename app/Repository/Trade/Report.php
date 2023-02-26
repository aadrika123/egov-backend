<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeRenewal;
use App\Models\Trade\TradeTransaction;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use Carbon\Carbon;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Report implements IReport
{
    use Auth;
    use Workflow;

    protected $_common;
    protected $_modelWard;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
    }

    public function CollectionReports(Request $request)
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
            $where = "WHERE trade_transactions.status IN(1,2) AND trade_transactions.tran_date between '$fromDate' AND '$uptoDate' ";
            if($request->wardId)
            {
                $wardId = $request->wardId;
                $where .= " AND trade_transactions.ward_id =  $wardId ";
            }
            if($request->userId)
            {
                $userId = $request->userId;
                $where .= " AND trade_transactions.emp_dtl_id =  $userId ";
            }
            if($request->paymentMode)
            {
                $paymentMode = strtoupper($request->paymentMode);
                $where .= " AND upper(trade_transactions.payment_mode) =  upper('$paymentMode') ";
            }
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $where .= " AND trade_transactions.ulb_id =  $ulbId ";
            $active = TradeTransaction::select(
                            DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                        active_trade_licences.application_no AS application_no,
                                        (
                                            CASE WHEN active_trade_licences.license_no='' OR active_trade_licences.license_no IS NULL THEN 'N/A' 
                                            ELSE active_trade_licences.license_no END
                                        ) AS license_no,
                                        owner_detail.owner_name,
                                        owner_detail.mobile_no,
                                        active_trade_licences.firm_name AS firm_name,
                                        trade_transactions.tran_date,
                                        trade_transactions.payment_mode AS transaction_mode,
                                        trade_transactions.paid_amount,
                                        (
                                            CASE WHEN upper(trade_transactions.payment_mode) NOT IN('ONLINE','ONL') THEN users.user_name 
                                            ELSE 'N/A' END
                                        ) AS emp_name,
                                        trade_transactions.tran_no,
                                        (
                                            CASE WHEN trade_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                            ELSE trade_cheque_dtls.cheque_no END
                                        ) AS cheque_no,
                                        (
                                            CASE WHEN trade_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                            ELSE trade_cheque_dtls.bank_name END
                                        ) AS bank_name,
                                        (
                                            CASE WHEN trade_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                            ELSE trade_cheque_dtls.branch_name END
                                        ) AS branch_name
                            "),
                        )
                    ->JOIN("active_trade_licences","active_trade_licences.id","trade_transactions.temp_id")
                    ->JOIN("ulb_ward_masters","ulb_ward_masters.id","active_trade_licences.ward_id")
                    ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id")
                    ->LEFTJOIN("users","users.id","trade_transactions.emp_dtl_id")
                    ->LEFTJOIN(DB::RAW("(
                        SELECT DISTINCT(active_trade_owners.temp_id) AS temp_id,
                            STRING_AGG(owner_name,',') AS  owner_name,
                            STRING_AGG(mobile_no::TEXT,',') AS  mobile_no
                        FROM  active_trade_owners
                        JOIN trade_transactions ON trade_transactions.temp_id = active_trade_owners.temp_id
                        $where
                        GROUP BY active_trade_owners.temp_id
                        ) AS owner_detail"),function($join){
                            $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                        })
                    ->WHEREIN("trade_transactions.status",[1,2])
                    ->WHEREBETWEEN("trade_transactions.tran_date",[$fromDate,$uptoDate]);
            
            $approved = TradeTransaction::select(
                        DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                    trade_licences.application_no AS application_no,
                                    (
                                        CASE WHEN trade_licences.license_no='' OR trade_licences.license_no IS NULL THEN 'N/A' 
                                        ELSE trade_licences.license_no END
                                    ) AS license_no,
                                    owner_detail.owner_name,
                                    owner_detail.mobile_no,
                                    trade_licences.firm_name AS firm_name,
                                    trade_transactions.tran_date,
                                    trade_transactions.payment_mode AS transaction_mode,
                                    trade_transactions.paid_amount,
                                    (
                                        CASE WHEN upper(trade_transactions.payment_mode) NOT IN('ONLINE','ONL') THEN users.user_name 
                                        ELSE 'N/A' END
                                    ) AS emp_name,
                                    trade_transactions.tran_no,
                                    (
                                        CASE WHEN trade_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                        ELSE trade_cheque_dtls.cheque_no END
                                    ) AS cheque_no,
                                    (
                                        CASE WHEN trade_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                        ELSE trade_cheque_dtls.bank_name END
                                    ) AS bank_name,
                                    (
                                        CASE WHEN trade_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                        ELSE trade_cheque_dtls.branch_name END
                                    ) AS branch_name
                                    
                        "),
                    )
                ->JOIN("trade_licences","trade_licences.id","trade_transactions.temp_id")
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","trade_licences.ward_id")
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id")
                ->LEFTJOIN("users","users.id","trade_transactions.emp_dtl_id")
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(trade_owners.temp_id) AS temp_id,
                        STRING_AGG(owner_name,',') AS  owner_name,
                        STRING_AGG(mobile_no::TEXT,',') AS  mobile_no
                    FROM  trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id
                    $where
                    GROUP BY trade_owners.temp_id
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->WHEREIN("trade_transactions.status",[1,2])
                ->WHEREBETWEEN("trade_transactions.tran_date",[$fromDate,$uptoDate]);

            $rejected = TradeTransaction::select(
                    DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                rejected_trade_licences.application_no AS application_no,
                                (
                                    CASE WHEN rejected_trade_licences.license_no='' OR rejected_trade_licences.license_no IS NULL THEN 'N/A' 
                                    ELSE rejected_trade_licences.license_no END
                                ) AS license_no,
                                owner_detail.owner_name,
                                owner_detail.mobile_no,
                                rejected_trade_licences.firm_name AS firm_name,
                                trade_transactions.tran_date,
                                trade_transactions.payment_mode AS transaction_mode,
                                trade_transactions.paid_amount,
                                (
                                    CASE WHEN upper(trade_transactions.payment_mode) NOT IN('ONLINE','ONL') THEN users.user_name 
                                    ELSE 'N/A' END
                                ) AS emp_name,
                                trade_transactions.tran_no,
                                (
                                    CASE WHEN trade_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.cheque_no END
                                ) AS cheque_no,
                                (
                                    CASE WHEN trade_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.bank_name END
                                ) AS bank_name,
                                (
                                    CASE WHEN trade_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.branch_name END
                                ) AS branch_name
                    "),
                )
                ->JOIN("rejected_trade_licences","rejected_trade_licences.id","trade_transactions.temp_id")
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","rejected_trade_licences.ward_id")
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id")
                ->LEFTJOIN("users","users.id","trade_transactions.emp_dtl_id")
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(rejected_trade_owners.temp_id) AS temp_id,
                        STRING_AGG(owner_name,',') AS  owner_name,
                        STRING_AGG(mobile_no::TEXT,',') AS  mobile_no
                    FROM  rejected_trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id = rejected_trade_owners.temp_id
                    $where
                    GROUP BY rejected_trade_owners.temp_id
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->WHEREIN("trade_transactions.status",[1,2])
                ->WHEREBETWEEN("trade_transactions.tran_date",[$fromDate,$uptoDate]);
            $old = TradeTransaction::select(
                    DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                trade_renewals.application_no AS application_no,
                                (
                                    CASE WHEN trade_renewals.license_no='' OR trade_renewals.license_no IS NULL THEN 'N/A' 
                                    ELSE trade_renewals.license_no END
                                ) AS license_no,
                                owner_detail.owner_name,
                                owner_detail.mobile_no,
                                trade_renewals.firm_name AS firm_name,
                                trade_transactions.tran_date,
                                trade_transactions.payment_mode AS transaction_mode,
                                trade_transactions.paid_amount,
                                (
                                    CASE WHEN upper(trade_transactions.payment_mode) NOT IN('ONLINE','ONL') THEN users.user_name 
                                    ELSE 'N/A' END
                                ) AS emp_name,
                                trade_transactions.tran_no,
                                (
                                    CASE WHEN trade_cheque_dtls.cheque_no IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.cheque_no END
                                ) AS cheque_no,
                                (
                                    CASE WHEN trade_cheque_dtls.bank_name IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.bank_name END
                                ) AS bank_name,
                                (
                                    CASE WHEN trade_cheque_dtls.branch_name IS NULL THEN 'N/A' 
                                    ELSE trade_cheque_dtls.branch_name END
                                ) AS branch_name
                    "),
                )
                ->JOIN("trade_renewals","trade_renewals.id","trade_transactions.temp_id")
                ->JOIN("ulb_ward_masters","ulb_ward_masters.id","trade_renewals.ward_id")
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id")
                ->LEFTJOIN("users","users.id","trade_transactions.emp_dtl_id")
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(trade_owners.temp_id) AS temp_id,
                        STRING_AGG(owner_name,',') AS  owner_name,
                        STRING_AGG(mobile_no::TEXT,',') AS  mobile_no
                    FROM  trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id
                    $where
                    GROUP BY trade_owners.temp_id
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->WHEREIN("trade_transactions.status",[1,2])
                ->WHEREBETWEEN("trade_transactions.tran_date",[$fromDate,$uptoDate]);
                ;
                
            if($wardId)
            {
                $active=$active->where("ulb_ward_masters.id",$wardId);
                $approved=$approved->where("ulb_ward_masters.id",$wardId);
                $rejected=$rejected->where("ulb_ward_masters.id",$wardId);
                $old=$old->where("ulb_ward_masters.id",$wardId);
            }
            if($userId)
            {
                $active=$active->where("trade_transactions.emp_dtl_id",$userId);
                $approved=$approved->where("trade_transactions.emp_dtl_id",$userId);
                $rejected=$rejected->where("trade_transactions.emp_dtl_id",$userId);
                $old=$old->where("trade_transactions.emp_dtl_id",$userId);
            }
            if($paymentMode)
            {
                $active=$active->where(DB::raw("upper(trade_transactions.payment_mode)"),$paymentMode);
                $approved=$approved->where(DB::raw("upper(trade_transactions.payment_mode)"),$paymentMode);
                $rejected=$rejected->where(DB::raw("upper(trade_transactions.payment_mode)"),$paymentMode);
                $old=$old->where(DB::raw("upper(trade_transactions.payment_mode)"),$paymentMode);
            }
            if($ulbId)
            {
                $active=$active->where("trade_transactions.ulb_id",$ulbId);
                $approved=$approved->where("trade_transactions.ulb_id",$ulbId);
                $rejected=$rejected->where("trade_transactions.ulb_id",$ulbId);
                $old=$old->where("trade_transactions.ulb_id",$ulbId);
            }
            $data = $active->union($approved)
                            ->union($rejected)
                            ->union($old);
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

    public function teamSummary (Request $request)
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
                $paymentMode = strtoupper($request->paymentMode);
            }
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            $data = TradeTransaction::select(
                    DB::raw("sum(trade_transactions.paid_amount) as amount, 
                            count(trade_transactions.id) total_no,
                            users.id as user_id, 
                            case when users.id  is null then 'Online' else users.user_name 
                            end as user_name ")
                    )
                    ->LEFTJOIN("users",function($join){
                        $join->on("users.id","=","trade_transactions.emp_dtl_id")
                        ->whereNotIn(DB::raw("upper(trade_transactions.payment_mode)"),["ONLINE","ONL"]);
                    })
                    ->whereIN("trade_transactions.status",[1,2])
                    ->WHEREBETWEEN("trade_transactions.tran_date",[$fromDate,$uptoDate]);
            if($wardId)
            {
                $data=$data->where("trade_transactions.ward_id",$wardId);
            }
            if($userId)
            {
                $data=$data->where("trade_transactions.emp_dtl_id",$userId);
            }
            if($paymentMode)
            {
                $data=$data->where(DB::raw("upper(trade_transactions.payment_mode)"),$paymentMode);
            }
            if($ulbId)
            {
                $data=$data->where("trade_transactions.ulb_id",$ulbId);
            }
            $data=$data->groupBy(["users.id", "users.user_name"]);
            
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
            // dd(DB::getQueryLog());
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function valideAndExpired(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $uptoDate = Carbon::now()->format("Y-m-d");
            $licenseNo = null;
            $oprater=null;
            if($request->licenseNo)
            {
                $licenseNo = $request->licenseNo;
            }
            if(strtoupper($request->licenseStatus)=="EXPIRED")
            {
                $oprater = "<";
            }
            if(strtoupper($request->licenseStatus)=="VALID")
            {
                $oprater = ">=";
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            if($request->wardId)
            {
                $wardId = $request->wardId;
            }
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            $data = TradeLicence::select("trade_licences.id","trade_licences.ward_id",
                        "trade_licences.ulb_id","trade_licences.application_no","trade_licences.provisional_license_no",
                        "trade_licences.application_date","trade_licences.license_no","trade_licences.license_date",
                        "trade_licences.valid_from","trade_licences.valid_upto","trade_licences.firm_name",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'approve' as type")
            
                    )
                    ->join("ulb_ward_masters","ulb_ward_masters.id","trade_licences.ward_id")                   
                    ->where("trade_licences.ulb_id",$ulbId);
            if($oprater)
            {
                $data = $data->where("trade_licences.valid_upto",$oprater,$uptoDate);
            }
            if($wardId)
            {
                $data = $data->where("trade_licences.ward_id",$wardId);
            }
            if($licenseNo)
            {
                $data = $data->where('trade_licences.license_no', 'ILIKE', "%" . $licenseNo . "%");
            }
            if((!$oprater) && $licenseNo)
            {
                $old = TradeRenewal::select("trade_renewals.id","trade_renewals.ward_id",
                        "trade_renewals.ulb_id","trade_renewals.application_no","trade_renewals.provisional_license_no",
                        "trade_renewals.application_date","trade_renewals.license_no","trade_renewals.license_date",
                        "trade_renewals.valid_from","trade_renewals.valid_upto","trade_renewals.firm_name",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'old' as type")
            
                    )
                    ->join("ulb_ward_masters","ulb_ward_masters.id","trade_renewals.ward_id")                   
                    ->where("trade_renewals.ulb_id",$ulbId)
                    ->where('trade_renewals.license_no', 'ILIKE', '%' . $licenseNo . '%');
                    if($wardId)
                    {
                        $old = $old->where("trade_renewals.ward_id",$wardId);
                    }
                    $data= $data->union($old);
                    
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
            // dd(DB::getQueryLog());
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$list,$apiId, $version, $queryRunTime,$action,$deviceId);
            
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function CollectionSummary(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            if($request->fromDate)
            {
                $fromDate = $request->fromDate;
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            $where=" AND trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate' ";
            if($request->wardId)
            {
                $wardId = $request->wardId;
                $where.=" AND trade_transactions.ward_id = $wardId ";
            }
            if($request->userId)
            {
                $userId = $request->userId;
                $where.=" AND trade_transactions.emp_dtl_id = $userId ";
            }            
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $where.=" AND trade_transactions.ulb_id = $ulbId ";
            $total_transacton = DB::select("
                            SELECT
                                sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                count(trade_transactions.id) as total_transaction,
                                count(trade_transactions.temp_id) as total_consumer,
                                payment_modes.mode as payment_mode
                            FROM ( 
                                    SELECT distinct(payment_mode) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON trade_transactions.payment_mode = payment_modes.mode
                                $where
                            GROUP BY  payment_modes.mode
                            ");
            $deactivate_transacton = DB::select("
                            SELECT
                                sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                count(trade_transactions.id) as total_transaction,
                                count(trade_transactions.temp_id) as total_consumer,
                                payment_modes.mode as payment_mode
                            FROM ( 
                                    SELECT distinct(payment_mode) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON trade_transactions.payment_mode = payment_modes.mode
                                $where AND trade_transactions.status IN(0,3)
                            GROUP BY  payment_modes.mode
                            ");
            $actual_transacton = DB::select("
                            SELECT
                                sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                count(trade_transactions.id) as total_transaction,
                                count(trade_transactions.temp_id) as total_consumer,
                                payment_modes.mode as payment_mode
                            FROM ( 
                                    SELECT distinct(payment_mode) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON trade_transactions.payment_mode = payment_modes.mode
                                $where AND trade_transactions.status IN(1,2)
                            GROUP BY  payment_modes.mode
                            ");
            $application_type_transacton = DB::select("
                            SELECT
                                sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                count(trade_transactions.id) as total_transaction,
                                count(trade_transactions.temp_id) as total_consumer,
                                trade_param_application_types.application_type,
                                trade_param_application_types.id 
                            FROM trade_param_application_types
                            LEFT JOIN trade_transactions ON trade_transactions.tran_type =  trade_param_application_types.application_type 
                                $where AND trade_transactions.status IN(1,2)
                            GROUP BY  trade_param_application_types.application_type ,trade_param_application_types.id
                            ORDER BY trade_param_application_types.id
                            ");
            $total_transacton=collect($total_transacton);
            $deactivate_transacton=collect($deactivate_transacton);
            $actual_transacton=collect($actual_transacton);
            $application_type_transacton=collect($application_type_transacton);
            
            $total_collection_amount = $total_transacton->sum("amount");
            $total_collection_consumer = $total_transacton->sum("total_consumer");
            $total_collection_transection = $total_transacton->sum("total_transaction");

            $deactivate_collection_amount = $deactivate_transacton->sum("amount");
            $deactivate_collection_consumer = $deactivate_transacton->sum("total_consumer");
            $deactivate_collection_transection = $deactivate_transacton->sum("total_transaction");

            $actual_collection_amount = $actual_transacton->sum("amount");
            $actual_collection_consumer = $actual_transacton->sum("total_consumer");
            $actual_collection_transection = $actual_transacton->sum("total_transaction");

            $application_type_collection_amount = $application_type_transacton->sum("amount");
            $application_type_collection_consumer = $application_type_transacton->sum("total_consumer");
            $application_type_collection_transection = $application_type_transacton->sum("total_transaction");

            $data["total_collection"]["mode_wise"] = $total_transacton->all();
            $data["total_collection"]["total"] = [
                    "payment_mode"=>"total",
                    "amount"=>$total_collection_amount,
                    "total_transaction"=>$total_collection_transection,
                    "total_consumer"=>$total_collection_consumer,
                ];
            $data["deactivate_collection"]["mode_wise"] = $deactivate_transacton->all();
            $data["deactivate_collection"]["total"] = [
                    "payment_mode"=>"total",
                    "amount"=>$deactivate_collection_amount,
                    "total_transaction"=>$deactivate_collection_transection,
                    "total_consumer"=>$deactivate_collection_consumer,
                ];
            $data["actual_collection"]["mode_wise"] = $actual_transacton->all();
            $data["actual_collection"]["total"] = [
                    "payment_mode"=>"total",
                    "amount"=>$actual_collection_amount,
                    "total_transaction"=>$actual_collection_transection,
                    "total_consumer"=>$actual_collection_consumer,
                ];
            $data["application_collection"]["mode_wise"] = $application_type_transacton->all();
            $data["application_collection"]["total"] = [
                    "payment_mode"=>"total",
                    "amount"=>$application_type_collection_amount,
                    "total_transaction"=>$application_type_collection_transection,
                    "total_consumer"=>$application_type_collection_consumer,
                ];

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
}
