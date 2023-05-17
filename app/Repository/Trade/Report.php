<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Trade\RejectedTradeLicence;
use App\Models\Trade\RejectedTradeNoticeConsumerDtl;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeNoticeConsumerDtl;
use App\Models\Trade\TradeRenewal;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfTrack;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use Carbon\Carbon;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Report implements IReport
{
    use Auth;
    use Workflow;

    protected $_MODEL_WARD;
    protected $_COMMON_FUNCTION;
    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;
    public function __construct()
    {
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_MODEL_WARD = new ModelWard();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
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

            $data = TradeLicence::select("trade_licences.id","trade_licences.ward_id","trade_licences.application_type_id",
                        "trade_licences.ulb_id","trade_licences.application_no","trade_licences.provisional_license_no",
                        "trade_licences.application_date","trade_licences.license_no","trade_licences.license_date",
                        "trade_licences.valid_from","trade_licences.valid_upto","trade_licences.firm_name",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'approve' as type, ulb_masters.ulb_name")
            
                    )
                    ->join("ulb_masters","ulb_masters.id","trade_licences.ulb_id")
                    ->join("ulb_ward_masters","ulb_ward_masters.id","trade_licences.ward_id");
                    
            if($oprater)
            {
                $data = $data->where("trade_licences.valid_upto",$oprater,$uptoDate);
            }
            if(strtoupper($request->licenseStatus)=="TO BE EXPIRED")
            {
                $data = $data->whereBetween("trade_licences.valid_upto",[$uptoDate,(new Carbon($uptoDate))->addDays(30)->format("Y-m-d")]);
            }
            if($wardId)
            {
                $data = $data->where("trade_licences.ward_id",$wardId);
            }
            if($ulbId)
            {
                $data = $data->where("trade_licences.ulb_id",$ulbId);
            }
            if($licenseNo)
            {
                $data = $data->where('trade_licences.license_no', 'ILIKE', "%" . $licenseNo . "%");
            }
            if((!$oprater) && $licenseNo)
            {
                $old = TradeRenewal::select("trade_renewals.id","trade_renewals.ward_id","trade_renewals.application_type_id",
                        "trade_renewals.ulb_id","trade_renewals.application_no","trade_renewals.provisional_license_no",
                        "trade_renewals.application_date","trade_renewals.license_no","trade_renewals.license_date",
                        "trade_renewals.valid_from","trade_renewals.valid_upto","trade_renewals.firm_name",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'old' as type , ulb_masters.ulb_name")
            
                    )
                    ->join("ulb_masters","ulb_masters.id","trade_renewals.ulb_id")
                    ->join("ulb_ward_masters","ulb_ward_masters.id","trade_renewals.ward_id") 
                    ->where('trade_renewals.license_no', 'ILIKE', '%' . $licenseNo . '%');
                    if($wardId)
                    {
                        $old = $old->where("trade_renewals.ward_id",$wardId);
                    }
                    if($ulbId)
                    {
                        $old = $old->where("trade_renewals.ulb_id",$ulbId);
                    }
                    $data= $data->union($old)->orderBy("application_date");
                    
            }
            $perPage = $request->perPage ? $request->perPage :  10;
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
                                    SELECT distinct(upper(payment_mode)) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON upper(trade_transactions.payment_mode) = upper(payment_modes.mode)
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
                                    SELECT distinct(upper(payment_mode)) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON upper(trade_transactions.payment_mode) = upper(payment_modes.mode)
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
                                    SELECT distinct(upper(payment_mode)) as mode
                                    FROM trade_transactions 
                                ) payment_modes
                            LEFT JOIN trade_transactions ON upper(trade_transactions.payment_mode) = upper(payment_modes.mode)
                                $where AND trade_transactions.status IN(1,2)
                            GROUP BY  payment_modes.mode
                            ");
            $application_type_transacton = DB::select("
                            SELECT
                                sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                count(trade_transactions.id) as total_transaction,
                                count(trade_transactions.temp_id) as total_consumer,
                                initcap(trade_param_application_types.application_type) as application_type ,
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
                    "payment_mode"=>"Total",
                    "amount"=>$total_collection_amount,
                    "total_transaction"=>$total_collection_transection,
                    "total_consumer"=>$total_collection_consumer,
                ];
            $data["deactivate_collection"]["mode_wise"] = $deactivate_transacton->all();
            $data["deactivate_collection"]["total"] = [
                    "payment_mode"=>"Total",
                    "amount"=>$deactivate_collection_amount,
                    "total_transaction"=>$deactivate_collection_transection,
                    "total_consumer"=>$deactivate_collection_consumer,
                ];
            $data["actual_collection"]["mode_wise"] = $actual_transacton->all();
            $data["actual_collection"]["total"] = [
                    "payment_mode"=>"Total",
                    "amount"=>$actual_collection_amount,
                    "total_transaction"=>$actual_collection_transection,
                    "total_consumer"=>$actual_collection_consumer,
                ];
            $data["application_collection"]["mode_wise"] = $application_type_transacton->all();
            $data["application_collection"]["total"] = [
                    "payment_mode"=>"Total",
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

    public function tradeDaseboard(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
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
            $fromDate = $fromYear."-04-01";
            $uptoDate = $toYear."-03-31";
                       
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $data = DB::select("
                                select count(license.id) , application_type 
                                from trade_param_application_types
                                left join (
                                        (
                                            select id,application_date,application_type_id
                                            from active_trade_licences
                                            where active_trade_licences.application_date between '$fromDate' and '$uptoDate'
                                                AND ulb_id = $ulbId
                                        )
                                        union(
                                            select id,application_date,application_type_id
                                            from rejected_trade_licences
                                            where rejected_trade_licences.application_date between '$fromDate' and '$uptoDate'
                                                AND ulb_id = $ulbId
                                        )
                                        union(
                                            select id,application_date,application_type_id
                                            from trade_licences
                                            where trade_licences.application_date between '$fromDate' and '$uptoDate'
                                                AND ulb_id = $ulbId
                                        )
                                        union(
                                            select id,application_date,application_type_id
                                            from trade_renewals
                                            where trade_renewals.application_date between '$fromDate' and '$uptoDate'
                                                AND ulb_id = $ulbId
                                        )
                                    ) license  on trade_param_application_types.id = license.application_type_id                                
                                    
                                    group by application_type,trade_param_application_types.id
                                    ORDER BY trade_param_application_types.id
                            ");
            

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function applicationTypeCollection(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;

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
            $fromDate = $fromYear."-04-01";
            $uptoDate = $toYear."-03-31";
            
            $where=" AND trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate' ";

            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $where.=" AND trade_transactions.ulb_id = $ulbId ";

            $application_type_transacton = DB::select("
                                                        SELECT
                                                            sum(COALESCE(trade_transactions.paid_amount,0)) as amount, 
                                                            count(trade_transactions.id) as total_transaction,
                                                            count(trade_transactions.temp_id) as total_consumer,
                                                            initcap(trade_param_application_types.application_type) as application_type ,
                                                            trade_param_application_types.id 
                                                        FROM trade_param_application_types
                                                        LEFT JOIN trade_transactions ON trade_transactions.tran_type =  trade_param_application_types.application_type 
                                                            $where AND trade_transactions.status IN(1,2)
                                                        GROUP BY  trade_param_application_types.application_type ,trade_param_application_types.id
                                                        ORDER BY trade_param_application_types.id
                                                        ");
            $application_type_transacton=collect($application_type_transacton);
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",remove_null($application_type_transacton),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function userAppliedApplication(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            $ActiveLicence = ActiveTradeLicence::select(
                "active_trade_licences.id",
                "active_trade_licences.application_no",
                "active_trade_licences.provisional_license_no",
                "active_trade_licences.license_no",
                "active_trade_licences.license_date",
                "active_trade_licences.valid_from",
                "active_trade_licences.valid_upto",
                "active_trade_licences.document_upload_status",
                "active_trade_licences.payment_status",
                "active_trade_licences.pending_status",
                "active_trade_licences.firm_name",
                "active_trade_licences.application_date",
                "active_trade_licences.apply_from",
                "active_trade_licences.application_type_id",
                "active_trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'active' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","active_trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                    STRING_AGG(guardian_name,',') AS guardian_name,
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                    STRING_AGG(email_id,',') AS email_id,
                                    active_trade_owners.temp_id
                                    FROM active_trade_owners 
                                    JOIN active_trade_licences on active_trade_licences.user_id = $refUserId 
                                        AND active_trade_licences.id = active_trade_owners.temp_id 
                                    WHERE active_trade_owners.is_active = true
                                    GROUP BY active_trade_owners.temp_id
                                    )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_active", true)
                ->where("active_trade_licences.user_id", $refUserId);
                // ->get();
            $RejectedLicence = RejectedTradeLicence::select(
                "rejected_trade_licences.id",
                "rejected_trade_licences.application_no",
                "rejected_trade_licences.provisional_license_no",
                "rejected_trade_licences.license_no",
                "rejected_trade_licences.license_date",
                "rejected_trade_licences.valid_from",
                "rejected_trade_licences.valid_upto",
                "rejected_trade_licences.document_upload_status",
                "rejected_trade_licences.payment_status",
                "rejected_trade_licences.pending_status",
                "rejected_trade_licences.firm_name",
                "rejected_trade_licences.application_date",
                "rejected_trade_licences.apply_from",
                "rejected_trade_licences.application_type_id",
                "rejected_trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'rejected' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","rejected_trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                    STRING_AGG(guardian_name,',') AS guardian_name,
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                    STRING_AGG(email_id,',') AS email_id,
                                    rejected_trade_owners.temp_id
                                    FROM rejected_trade_owners
                                    JOIN rejected_trade_licences on rejected_trade_licences.user_id = $refUserId 
                                        AND rejected_trade_licences.id = rejected_trade_owners.temp_id 
                                    WHERE rejected_trade_owners.is_active = true
                                    GROUP BY rejected_trade_owners.temp_id
                                    )owner"), function ($join) {
                    $join->on("owner.temp_id", "rejected_trade_licences.id");
                })
                ->where("rejected_trade_licences.is_active", true)
                ->where("rejected_trade_licences.user_id", $refUserId);
                // ->get();

            $ApprovedLicence = TradeLicence::select(
                "trade_licences.id",
                "trade_licences.application_no",
                "trade_licences.provisional_license_no",
                "trade_licences.license_no",
                "trade_licences.license_date",
                "trade_licences.valid_from",
                "trade_licences.valid_upto",
                "trade_licences.document_upload_status",
                "trade_licences.payment_status",
                "trade_licences.pending_status",
                "trade_licences.firm_name",
                "trade_licences.application_date",
                "trade_licences.apply_from",
                "trade_licences.application_type_id",
                "trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'approved' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        trade_owners.temp_id
                                        FROM trade_owners
                                        JOIN trade_licences on trade_licences.user_id = $refUserId 
                                        AND trade_licences.id = trade_owners.temp_id 
                                        WHERE trade_owners.is_active = true
                                        GROUP BY trade_owners.temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "trade_licences.id");
                })
                ->where("trade_licences.is_active", true)
                ->where("trade_licences.user_id", $refUserId);

            $ActiveLicence = $ActiveLicence->WHEREBETWEEN("application_date",[$fromDate,$uptoDate]);
            $RejectedLicence = $RejectedLicence->WHEREBETWEEN("application_date",[$fromDate,$uptoDate]);
            $ApprovedLicence = $ApprovedLicence->WHEREBETWEEN("application_date",[$fromDate,$uptoDate]);

            $ActiveLicence = $ActiveLicence->WHERE("ulb_id",$ulbId);
            $RejectedLicence = $RejectedLicence->WHERE("ulb_id",$ulbId);
            $ApprovedLicence = $ApprovedLicence->WHERE("ulb_id",$ulbId);

            if($wardId)
            {
                $ActiveLicence = $ActiveLicence->WHERE("ward_id",$wardId);
                $RejectedLicence = $RejectedLicence->WHERE("ward_id",$wardId);
                $ApprovedLicence = $ApprovedLicence->WHERE("ward_id",$wardId);
            }

            $final = $ActiveLicence->union($RejectedLicence)
                ->union($ApprovedLicence)
                ->get();
                // dd(DB::getQueryLog());
            $final->map(function($val){
                $option = [];
                $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                if(trim($val->license_type)=="approved" && $val->pending_status == 5 && $val->valid_upto < $nextMonth)
                {
                    $option[]="RENEWAL";
                }
                if(trim($val->license_type)=="approved" && $val->pending_status == 5 && $val->valid_upto >= Carbon::now()->format('Y-m-d'))
                {
                    $option[]="AMENDMENT";
                    $option[]="SURRENDER";
                }
                $val->option = $option;
                return $val;
            });
            return responseMsg(true, "", remove_null($final));
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function collectionPerfomance(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $refWorkflowId      = $this->_WF_MASTER_Id;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $data = (array) null;
            $wardId = null;

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
            $yFromDate = $fromYear."-04-01";
            $yUptoDate = $toYear."-03-31";

            $now = Carbon::now();

            $mFromDate = $now->startOfMonth()->format('Y-m-d');
            $mUptoDate = $now->endOfMonth()->format('Y-m-d');

            $wFromDate = $now->startOfWeek()->format('Y-m-d');
            $wUptoDate = $now->endOfWeek()->format('Y-m-d');

            $toDay = $now->format('Y-m-d');

            $yearlly = TradeTransaction::select(DB::RAW("
                                CASE WHEN SUM(COALESCE(trade_transactions.paid_amount,0)) IS NOT NULL THEN SUM(COALESCE(trade_transactions.paid_amount,0)) ELSE 0 END  as amount,
                                COUNT(DISTINCT(trade_transactions.temp_id)) AS total_application,
                                COUNT(DISTINCT(trade_transactions.id)) AS total_transection
                                "))
                            ->WHERE("trade_transactions.ulb_id", $ulbId )
                            ->WHEREIN("trade_transactions.status",[1,2]);

            $monthlly = TradeTransaction::select(DB::RAW("
                            CASE WHEN SUM(COALESCE(trade_transactions.paid_amount,0)) IS NOT NULL THEN SUM(COALESCE(trade_transactions.paid_amount,0)) ELSE 0 END  as amount,
                            COUNT(DISTINCT(trade_transactions.temp_id)) AS total_application,
                            COUNT(DISTINCT(trade_transactions.id)) AS total_transection
                            "))
                        ->WHERE("trade_transactions.ulb_id", $ulbId )
                        ->WHEREIN("trade_transactions.status",[1,2]);

            $weeklly = TradeTransaction::select(DB::RAW("
                            CASE WHEN SUM(COALESCE(trade_transactions.paid_amount,0)) IS NOT NULL THEN SUM(COALESCE(trade_transactions.paid_amount,0)) ELSE 0 END  as amount,
                            COUNT(DISTINCT(trade_transactions.temp_id)) AS total_application,
                            COUNT(DISTINCT(trade_transactions.id)) AS total_transection
                            "))
                        ->WHERE("trade_transactions.ulb_id", $ulbId )
                        ->WHEREIN("trade_transactions.status",[1,2]);

            $daylly = TradeTransaction::select(DB::RAW("
                            CASE WHEN SUM(COALESCE(trade_transactions.paid_amount,0)) IS NOT NULL THEN SUM(COALESCE(trade_transactions.paid_amount,0)) ELSE 0 END  as amount,
                            COUNT(DISTINCT(trade_transactions.temp_id)) AS total_application,
                            COUNT(DISTINCT(trade_transactions.id)) AS total_transection
                            "))
                        ->WHERE("trade_transactions.ulb_id", $ulbId )
                        ->WHEREIN("trade_transactions.status",[1,2]);

            $yearlly = $yearlly->WHEREBETWEEN('trade_transactions.tran_date',[$yFromDate, $yUptoDate]);
            $monthlly = $monthlly->WHEREBETWEEN('trade_transactions.tran_date',[$mFromDate, $mUptoDate]);
            $weeklly = $weeklly->WHEREBETWEEN('trade_transactions.tran_date',[$wFromDate, $wUptoDate]);
            $daylly = $daylly->WHEREBETWEEN('trade_transactions.tran_date',[$toDay, $toDay]);

            if($wardId)
            {
                $yearlly = $yearlly->WHERE('trade_transactions.ward_id',$wardId);
                $monthlly = $monthlly->WHERE('trade_transactions.ward_id',$wardId);
                $weeklly = $weeklly->WHERE('trade_transactions.ward_id',$wardId);
                $daylly = $daylly->WHERE('trade_transactions.ward_id',$wardId);
            }

            if(in_array(strtoupper($mUserType),["JSK","TC"]))
            {
                $yearlly = $yearlly->WHERE('trade_transactions.emp_dtl_id',$refUserId)
                            ->WHERENOTIN(DB::RAW('UPPER(trade_transactions.payment_mode)'),['ONLINE','ONL']);
                $monthlly = $monthlly->WHERE('trade_transactions.emp_dtl_id',$refUserId)
                            ->WHERENOTIN(DB::RAW('UPPER(trade_transactions.payment_mode)'),['ONLINE','ONL']);
                $weeklly = $weeklly->WHERE('trade_transactions.emp_dtl_id',$refUserId)
                            ->WHERENOTIN(DB::RAW('UPPER(trade_transactions.payment_mode)'),['ONLINE','ONL']);
                $daylly = $daylly->WHERE('trade_transactions.emp_dtl_id',$refUserId)
                            ->WHERENOTIN(DB::RAW('UPPER(trade_transactions.payment_mode)'),['ONLINE','ONL']);
            }

            $yearlly = $yearlly->get();
            $monthlly = $monthlly->get();
            $weeklly = $weeklly->get();
            $daylly = $daylly->get();

            // dd(DB::getQueryLog());

            $data["yearlly"]    = $yearlly;
            $data["monthlly"]   = $monthlly;
            $data["weeklly"]    = $weeklly;
            $data["daylly"]     = $daylly;

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);            

        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function ApplicantionTrackStatus(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $active = ActiveTradeLicence::select("active_trade_licences.id",
                                    "active_trade_licences.application_date",
                                    "active_trade_licences.ward_id",
                                    "active_trade_licences.document_upload_status",
                                    "active_trade_licences.payment_status",
                                    "active_trade_licences.pending_status",
                                    "active_trade_licences.is_parked",
                                    "active_trade_licences.workflow_id",
                                    "owners.owner_name","owners.mobile_no",
                                    DB::raw("ulb_ward_masters.ward_name as ward_no")
                        )
                        ->join("ulb_ward_masters","ulb_ward_masters.id","active_trade_licences.ward_id")
                        ->leftjoin(DB::raw("(
                            select string_agg(owner_name,',') as owner_name,
                                string_agg(mobile_no::text,',') as mobile_no,
                                active_trade_owners.temp_id
                            from active_trade_owners 
                            join active_trade_licences on active_trade_licences.id = active_trade_owners.temp_id
                            where active_trade_owners.is_active = true
                                and active_trade_licences.application_date between '$fromDate' and '$uptoDate'
                                and active_trade_licences.ulb_id = $ulbId" .
                                ($wardId ? " And active_trade_licences.ward_id = $wardId " : " ")
                                .
                                "
                            group by active_trade_owners.temp_id
                            )owners"),function($join){
                                $join->on("owners.temp_id","active_trade_licences.id");
                            })
                            ->whereBetween("active_trade_licences.application_date",[$fromDate,$uptoDate])
                            ->where("active_trade_licences.ulb_id",$ulbId);
                            if($wardId)
                            {
                                $active = $active->where("active_trade_licences.ward_id",$wardId);
                            }
            $rejected = RejectedTradeLicence::select("rejected_trade_licences.id",
                "rejected_trade_licences.application_date",
                "rejected_trade_licences.ward_id",
                "rejected_trade_licences.document_upload_status",
                "rejected_trade_licences.payment_status",
                "rejected_trade_licences.pending_status",
                "rejected_trade_licences.is_parked",
                "rejected_trade_licences.workflow_id",
                "owners.owner_name","owners.mobile_no",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
                )
                ->join("ulb_ward_masters","ulb_ward_masters.id","rejected_trade_licences.ward_id")
                ->leftjoin(DB::raw("(
                    select string_agg(owner_name,',') as owner_name,
                        string_agg(mobile_no::text,',') as mobile_no,
                        rejected_trade_owners.temp_id
                    from rejected_trade_owners 
                    join rejected_trade_licences on rejected_trade_licences.id = rejected_trade_owners.temp_id
                    where rejected_trade_owners.is_active = true
                        and rejected_trade_licences.application_date between '$fromDate' and '$uptoDate'
                        and rejected_trade_licences.ulb_id = $ulbId" .
                        ($wardId ? " And rejected_trade_licences.ward_id = $wardId " : " ")
                        .
                        "
                    group by rejected_trade_owners.temp_id
                    )owners"),function($join){
                        $join->on("owners.temp_id","rejected_trade_licences.id");
                    })
                    ->whereBetween("rejected_trade_licences.application_date",[$fromDate,$uptoDate])
                    ->where("rejected_trade_licences.ulb_id",$ulbId);
                    if($wardId)
                    {
                        $rejected = $rejected->where("rejected_trade_licences.ward_id",$wardId);
                    }
            $approved = TradeLicence::select("trade_licences.id",
                "trade_licences.application_date",
                "trade_licences.ward_id",
                "trade_licences.document_upload_status",
                "trade_licences.payment_status",
                "trade_licences.pending_status",
                "trade_licences.is_parked",
                "trade_licences.workflow_id",
                "owners.owner_name","owners.mobile_no",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
                )
                ->join("ulb_ward_masters","ulb_ward_masters.id","trade_licences.ward_id")
                ->leftjoin(DB::raw("(
                    select string_agg(owner_name,',') as owner_name,
                        string_agg(mobile_no::text,',') as mobile_no,
                        trade_owners.temp_id
                    from trade_owners 
                    join trade_licences on trade_licences.id = trade_owners.temp_id
                    where trade_licences.is_active = true
                        and trade_licences.application_date between '$fromDate' and '$uptoDate'
                        and trade_licences.ulb_id = $ulbId" .
                        ($wardId ? " And trade_licences.ward_id = $wardId " : " ")
                        .
                        "
                    group by trade_owners.temp_id
                    )owners"),function($join){
                        $join->on("owners.temp_id","trade_licences.id");
                    })
                    ->whereBetween("trade_licences.application_date",[$fromDate,$uptoDate])
                    ->where("trade_licences.ulb_id",$ulbId);
                    if($wardId)
                    {
                        $approved = $approved->where("trade_licences.ward_id",$wardId);
                    }
            $old = TradeRenewal::select("trade_renewals.id",
                "trade_renewals.application_date",
                "trade_renewals.ward_id",
                "trade_renewals.document_upload_status",
                "trade_renewals.payment_status",
                "trade_renewals.pending_status",
                "trade_renewals.is_parked",
                "trade_renewals.workflow_id",
                "owners.owner_name","owners.mobile_no",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
                )
                ->join("ulb_ward_masters","ulb_ward_masters.id","trade_renewals.ward_id")
                ->leftjoin(DB::raw("(
                    select string_agg(owner_name,',') as owner_name,
                        string_agg(mobile_no::text,',') as mobile_no,
                        trade_owners.temp_id
                    from trade_owners 
                    join trade_renewals on trade_renewals.id = trade_owners.temp_id
                    where trade_renewals.is_active = true
                        and trade_renewals.application_date between '$fromDate' and '$uptoDate'
                        and trade_renewals.ulb_id = $ulbId" .
                        ($wardId ? " And trade_renewals.ward_id = $wardId " : " ")
                        .
                        "
                    group by trade_owners.temp_id
                    )owners"),function($join){
                        $join->on("owners.temp_id","trade_renewals.id");
                    })
                    ->whereBetween("trade_renewals.application_date",[$fromDate,$uptoDate])
                    ->where("trade_renewals.ulb_id",$ulbId);
                    if($wardId)
                    {
                        $old = $old->where("trade_renewals.ward_id",$wardId);
                    }

            $data = $active
                    ->union($rejected)
                    ->union($approved)
                    ->union($old);
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $data->paginate($perPage);
            $items = $paginator->items();
            $total = $paginator->total();
            $numberOfPages = ceil($total/$perPage); 
            // $item2 =[];
            foreach($items as $key=>$val)
            {

                $level = DB::SELECT("
                select array_to_json(array_agg(row_to_json(levle))) as level
                from (
                        select workflow_tracks.track_date, workflow_tracks.forward_date, verification_status, 
                            ids.role_name
                        from workflow_tracks 
                        join(
                            select max(workflow_tracks.id)as id,wf_workflowrolemaps.serial_no,wf_roles.role_name
                            from workflow_tracks
                            inner join wf_roles on wf_roles.id = workflow_tracks.receiver_role_id 
                            inner join wf_workflowrolemaps on wf_workflowrolemaps.wf_role_id = wf_roles.id 
                                and wf_workflowrolemaps.workflow_id = ".$val["workflow_id"]."
                            where 
                                workflow_tracks.ref_table_id_value = ".$val["id"]."
                                and workflow_tracks.ref_table_dot_id = 'active_trade_licences' and workflow_tracks.status = true 
                                and workflow_tracks.workflow_id = ".$val["workflow_id"]."
                                and workflow_tracks.citizen_id is null 
                                and workflow_tracks.deleted_at is null 
                            group by wf_roles.id,wf_workflowrolemaps.serial_no,wf_roles.role_name
                            )ids on ids.id = workflow_tracks.id
                    )levle
                "); 
                $val["level"]  =(collect($level)->first())->level?(json_decode((collect($level)->first())->level,true)):[]; 
                    
                // $dealingStatus = WorkflowTrack::select("workflow_tracks.track_date",
                //                         "workflow_tracks.forward_date",
                //                         "verification_status",
                //                         DB::raw("'dealing' AS Role")
                //                         )
                //                     ->join("wf_roles","wf_roles.id","workflow_tracks.receiver_role_id")
                //                     ->where("wf_roles.role_name","ILIKE","Dealing Assistant")
                //                     ->where("workflow_tracks.ref_table_id_value",$val["id"])
                //                     ->where("workflow_tracks.ref_table_dot_id","active_trade_licences")
                //                     ->where("workflow_tracks.status",true)
                //                     ->where("workflow_tracks.workflow_id",$val["workflow_id"])
                //                     ->whereNull("workflow_tracks.citizen_id")
                //                     ->orderBy("workflow_tracks.id","DESC")
                //                     ->first();
                // $juniorStatus = WorkflowTrack::select("workflow_tracks.track_date",
                //                 "workflow_tracks.forward_date",
                //                 "verification_status",
                //                 DB::raw("'junior' AS Role")
                //                 )
                //                 ->join("wf_roles","wf_roles.id","workflow_tracks.receiver_role_id")
                //                 ->where("wf_roles.role_name","ILIKE","Junior Engineer")
                //                 ->where("workflow_tracks.ref_table_id_value",$val["id"])
                //                 ->where("workflow_tracks.ref_table_dot_id","active_trade_licences")
                //                 ->where("workflow_tracks.status",true)
                //                 ->where("workflow_tracks.workflow_id",$val["workflow_id"])
                //                 ->whereNull("workflow_tracks.citizen_id")
                //                 ->orderBy("workflow_tracks.id","DESC")
                //                 ->first();
                // $sectionStatus = WorkflowTrack::select("workflow_tracks.track_date",
                //                 "workflow_tracks.forward_date",
                //                 "verification_status",
                //                 DB::raw("'section' AS Role")
                //                 )
                //                 ->join("wf_roles","wf_roles.id","workflow_tracks.receiver_role_id")
                //                 ->where("wf_roles.role_name","ILIKE","Section Head")
                //                 ->where("workflow_tracks.ref_table_id_value",$val["id"])
                //                 ->where("workflow_tracks.ref_table_dot_id","active_trade_licences")
                //                 ->where("workflow_tracks.status",true)
                //                 ->where("workflow_tracks.workflow_id",$val["workflow_id"])
                //                 ->whereNull("workflow_tracks.citizen_id")
                //                 ->orderBy("workflow_tracks.id","DESC")
                //                 ->first();

                // $assistantStatus = WorkflowTrack::select("workflow_tracks.track_date",
                //                 "workflow_tracks.forward_date",
                //                 "verification_status",
                //                 DB::raw("'assistant' AS Role")
                //                 )
                //                 ->join("wf_roles","wf_roles.id","workflow_tracks.receiver_role_id")
                //                 ->where("wf_roles.role_name","ILIKE","Assistant Engineer")
                //                 ->where("workflow_tracks.ref_table_id_value",$val["id"])
                //                 ->where("workflow_tracks.ref_table_dot_id","active_trade_licences")
                //                 ->where("workflow_tracks.status",true)
                //                 ->where("workflow_tracks.workflow_id",$val["workflow_id"])
                //                 ->whereNull("workflow_tracks.citizen_id")
                //                 ->orderBy("workflow_tracks.id","DESC")
                //                 ->first();

                // $executiveStatus = WorkflowTrack::select("workflow_tracks.track_date",
                //                 "workflow_tracks.forward_date",
                //                 "verification_status",                                
                //                 DB::raw("'executive' AS Role")
                //                 )
                //                 ->join("wf_roles","wf_roles.id","workflow_tracks.receiver_role_id")
                //                 ->where("wf_roles.role_name","ILIKE","Executive Officer")
                //                 ->where("workflow_tracks.ref_table_id_value",$val["id"])
                //                 ->where("workflow_tracks.ref_table_dot_id","active_trade_licences")
                //                 ->where("workflow_tracks.status",true)
                //                 ->where("workflow_tracks.workflow_id",$val["workflow_id"])
                //                 ->whereNull("workflow_tracks.citizen_id")
                //                 ->orderBy("workflow_tracks.id","DESC")
                //                 ->first();
                // if($dealingStatus)
                // {
                //     $level[] = $dealingStatus;
                    
                // }
                // if($juniorStatus)
                // {
                //     $level[] = $juniorStatus;
                // }
                // if($sectionStatus)
                // {
                //     $level[] = $sectionStatus;
                // }
                // if($assistantStatus)
                // {
                //     $level[] = $assistantStatus;
                // }
                // if($executiveStatus)
                // {
                    
                //     $level[] = $executiveStatus;
                // }
                
                // $val["level"]=$level;
                // array_push($item2,$val);
            }               
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

    public function applicationAgentNotice(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            $active = ActiveTradeLicence::select(
                DB::RAW("
                trade_notice_consumer_dtls.notice_no,
                cast(trade_notice_consumer_dtls.notice_date as date) as notice_date,
                cast(trade_notice_consumer_dtls.created_at as date) as notice_apply_date,
                cast(active_trade_licences.application_date as date) as application_date,
                active_trade_licences.application_no,
                active_trade_licences.firm_name,
                active_trade_licences.address,
                owners.owner_name,owners.guardian_name,owners.mobile_no,
                ulb_ward_masters.ward_name as ward_no
                ")
            )
            ->JOIN("ulb_ward_masters","ulb_ward_masters.id","active_trade_licences.ward_id")
            ->JOIN("trade_notice_consumer_dtls","trade_notice_consumer_dtls.id","active_trade_licences.denial_id")
            ->LEFTJOIN(DB::RAW("(
                SELECT active_trade_owners.temp_id,
                    string_agg(owner_name,',')as owner_name,
                    string_agg(guardian_name,',')as guardian_name,
                    string_agg(mobile_no,',')as mobile_no
                FROM  active_trade_owners
                JOIN active_trade_licences ON active_trade_licences.id = active_trade_owners.temp_id
                WHERE active_trade_owners.is_active = TRUE
                    AND active_trade_licences.ulb_id = $ulbId
                    AND active_trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                    ".($wardId?" AND active_trade_licences.ward_id":"")."
                GROUP BY active_trade_owners.temp_id
            )owners"),function($join){
                $join->on("owners.temp_id","active_trade_licences.id");
            })
            ->WHERE("active_trade_licences.ulb_id",$ulbId)
            ->WHEREBETWEEN("active_trade_licences.application_date",[$fromDate,$uptoDate]);

            $rejected = RejectedTradeLicence::select(
                DB::RAW("
                trade_notice_consumer_dtls.notice_no,
                cast(trade_notice_consumer_dtls.notice_date as date) as notice_date,
                cast(trade_notice_consumer_dtls.created_at as date) as notice_apply_date,
                cast(rejected_trade_licences.application_date as date) as application_date,
                rejected_trade_licences.application_no,
                rejected_trade_licences.firm_name,
                rejected_trade_licences.address,
                owners.owner_name,owners.guardian_name,owners.mobile_no,
                ulb_ward_masters.ward_name as ward_no
                ")
            )
            ->JOIN("ulb_ward_masters","ulb_ward_masters.id","rejected_trade_licences.ward_id")
            ->JOIN("trade_notice_consumer_dtls","trade_notice_consumer_dtls.id","rejected_trade_licences.denial_id")
            ->LEFTJOIN(DB::RAW("(
                SELECT rejected_trade_owners.temp_id,
                    string_agg(owner_name,',')as owner_name,
                    string_agg(guardian_name,',')as guardian_name,
                    string_agg(mobile_no,',')as mobile_no
                FROM rejected_trade_owners
                JOIN rejected_trade_licences ON rejected_trade_licences.id = rejected_trade_owners.temp_id
                WHERE rejected_trade_owners.is_active = true
                    AND rejected_trade_licences.ulb_id = $ulbId
                    AND rejected_trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                    ".($wardId?" AND rejected_trade_licences.ward_id":"")."
                GROUP BY rejected_trade_owners.temp_id
            )owners"),function($join){
                $join->on("owners.temp_id","rejected_trade_licences.id");
            })
            ->WHERE("rejected_trade_licences.ulb_id",$ulbId)
            ->WHEREBETWEEN("rejected_trade_licences.application_date",[$fromDate,$uptoDate]);

            $approved = TradeLicence::select(
                DB::RAW("
                trade_notice_consumer_dtls.notice_no,
                cast(trade_notice_consumer_dtls.notice_date as date) as notice_date,
                cast(trade_notice_consumer_dtls.created_at as date) as notice_apply_date,
                cast(trade_licences.application_date as date) as application_date,
                trade_licences.application_no,
                trade_licences.firm_name,
                trade_licences.address,
                owners.owner_name,owners.guardian_name,owners.mobile_no,
                ulb_ward_masters.ward_name as ward_no
                ")
            )
            ->JOIN("ulb_ward_masters","ulb_ward_masters.id","trade_licences.ward_id")
            ->JOIN("trade_notice_consumer_dtls","trade_notice_consumer_dtls.id","trade_licences.denial_id")
            ->LEFTJOIN(DB::RAW("(
                SELECT trade_owners.temp_id,
                    string_agg(owner_name,',')as owner_name,
                    string_agg(guardian_name,',')as guardian_name,
                    string_agg(mobile_no,',')as mobile_no
                FROM trade_owners
                JOIN trade_licences ON trade_licences.id = trade_owners.temp_id
                WHERE trade_owners.is_active = true
                    AND trade_licences.ulb_id = $ulbId
                    AND trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                    ".($wardId?" AND trade_licences.ward_id":"")."
                GROUP BY trade_owners.temp_id
            )owners"),function($join){
                $join->on("owners.temp_id","trade_licences.id");
            })
            ->WHERE("trade_licences.ulb_id",$ulbId)
            ->WHEREBETWEEN("trade_licences.application_date",[$fromDate,$uptoDate]);

            $old = TradeRenewal::select(
                DB::RAW("
                trade_notice_consumer_dtls.notice_no,
                cast(trade_notice_consumer_dtls.notice_date as date) as notice_date,
                cast(trade_notice_consumer_dtls.created_at as date) as notice_apply_date,
                cast(trade_renewals.application_date as date) as application_date,
                trade_renewals.application_no,
                trade_renewals.firm_name,
                trade_renewals.address,
                owners.owner_name,owners.guardian_name,owners.mobile_no,
                ulb_ward_masters.ward_name as ward_no
                ")
            )
            ->JOIN("ulb_ward_masters","ulb_ward_masters.id","trade_renewals.ward_id")
            ->JOIN("trade_notice_consumer_dtls","trade_notice_consumer_dtls.id","trade_renewals.denial_id")
            ->LEFTJOIN(DB::RAW("(
                SELECT trade_owners.temp_id,
                    string_agg(owner_name,',')as owner_name,
                    string_agg(guardian_name,',')as guardian_name,
                    string_agg(mobile_no,',')as mobile_no
                FROM trade_owners
                JOIN trade_renewals ON trade_renewals.id = trade_owners.temp_id
                WHERE trade_owners.is_active = true
                    AND trade_renewals.ulb_id = $ulbId
                    AND trade_renewals.application_date BETWEEN '$fromDate' AND '$uptoDate'
                    ".($wardId?" AND trade_renewals.ward_id":"")."
                GROUP BY trade_owners.temp_id
            )owners"),function($join){
                $join->on("owners.temp_id","trade_renewals.id");
            })
            ->WHERE("trade_renewals.ulb_id",$ulbId)
            ->WHEREBETWEEN("trade_renewals.application_date",[$fromDate,$uptoDate]);            
            $data = $active
                    ->union($rejected)
                    ->union($approved)
                    ->union($old);

            $perPage = $request->perPage ? $request->perPage :10;
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

    public function noticeSummary(Request $request)
    {
        $metaData= collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }

            $data["approved"]= TradeNoticeConsumerDtl::WHEREBETWEEN(DB::RAW("cast(created_at as date)"),[$fromDate,$uptoDate])
                        ->WHERE("ulb_id",$ulbId)
                        ->count("id");
            $data["pending"] = ActiveTradeNoticeConsumerDtl::WHEREBETWEEN(DB::RAW("cast(created_at as date)"),[$fromDate,$uptoDate])
                        ->WHERE("ulb_id",$ulbId)
                        ->count("id");
            $data["rejected"] = RejectedTradeNoticeConsumerDtl::WHEREBETWEEN(DB::RAW("cast(created_at as date)"),[$fromDate,$uptoDate])
                        ->WHERE("ulb_id",$ulbId)
                        ->count("id");
            $data["total"] = $data["approved"]+$data["pending"]+$data["rejected"];

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,"",$data,$apiId, $version, $queryRunTime,$action,$deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }

    public function levelwisependingform(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            if ($request->ulbId) 
            {
                $ulbId = $request->ulbId;
            }
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $this->_WF_MASTER_Id)
                ->where('ulb_id', $ulbId)
                ->first();
            if (!$refWfWorkflow) 
            {
                throw new Exception("Workflow Not Available");
            }
            $workflow_id = $refWfWorkflow->id;
            $data = WfRole::SELECT(
                    "wf_roles.id",
                    "wf_roles.role_name",
                    DB::RAW("COUNT(active_trade_licences.id) AS total")
                )
                ->JOIN(DB::RAW("(
                                    SELECT distinct(wf_role_id) as wf_role_id
                                    FROM wf_workflowrolemaps 
                                    WHERE  wf_workflowrolemaps.is_suspended = false AND (forward_role_id IS NOT NULL OR backward_role_id IS NOT NULL)
                                        AND workflow_id IN(".$workflow_id.") 
                                    GROUP BY wf_role_id 
                                ) AS roles
                    "), function ($join) {
                    $join->on("wf_roles.id", "roles.wf_role_id");
                })
                ->LEFTJOIN("active_trade_licences", function ($join) use ($ulbId) {
                    $join->ON("active_trade_licences.current_role", "roles.wf_role_id")
                        ->WHERE("active_trade_licences.ulb_id", $ulbId)
                        ->WHERE("active_trade_licences.is_parked", FALSE)
                        ->WHEREIN("active_trade_licences.payment_status", [1,2]);                        
                })
                ->GROUPBY(["wf_roles.id", "wf_roles.role_name"])
                ->UNION(
                    ActiveTradeLicence::SELECT(
                        DB::RAW("8 AS id, 'JSK' AS role_name,
                                COUNT(active_trade_licences.id)")
                    )
                        ->WHERE("active_trade_licences.ulb_id", $ulbId)
                        ->WHERE("active_trade_licences.is_parked", FALSE)
                        ->WHERE(function ($where) {
                            $where->WHERE("active_trade_licences.payment_status", "=", 0)
                                ->ORWHERENULL("active_trade_licences.payment_status");
                        })
                )
                ->GET();
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true,["header"=>"LEVEL PENDING REPORT"] , $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        }
        catch (Exception $e) {
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
            $header = $userName = $roleName = null;
            $allRolse = $this->_COMMON_FUNCTION->getAllRoles($refUserId,$ulbId,$this->_WF_MASTER_Id,0,TRUE);
            $joins = "join";
            if ($request->ulbId) 
            {
                $ulbId = $request->ulbId;
            }
            if ($request->userId) 
            {
                $userId = $request->userId;                
                $userName = DB::TABLE('users')->find($userId )->name??"";
                $roleId2 = ($this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_WF_MASTER_Id))->role_id ?? 0;
            }
            if ($request->roleId) 
            {
                $roleId = $request->roleId;
                $currentRole = (array_values(collect($allRolse)->where("id",$roleId)->toArray()))[0]??[];
                
                $roleName = $currentRole["role_name"]??"";
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) 
            {                
                throw new Exception("Invalid RoleId Pass");
            }
            if($roleName)
            {
                $header = ["header"=>"PENDING AT $roleName"];
            }
            if($userName)
            {
                $header = ["header"=>"PENDING AT $userName"];
            }
            if (in_array($roleId, [8])) 
            {
                $joins = "leftjoin";
            }

            // DB::enableQueryLog();
            $data = ActiveTradeLicence::SELECT(
                    DB::RAW(
                        "count(active_trade_licences.id),
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
                    function ($join) use ($roleId) 
                    {
                        if (!in_array($roleId, [11, 8])) 
                        {
                            $join->on("users_role.wf_role_id", "=", "active_trade_licences.current_role")
                                ->where("active_trade_licences.ward_id", DB::raw("ANY (ward_ids::int[])"));
                        } 
                        if(in_array($roleId, [11]))
                        {
                            $join->on("users_role.wf_role_id", "=", "active_trade_licences.current_role");
                        }
                        if(in_array($roleId, [8]))
                        {
                            $join->on(DB::raw("1"), DB::raw("1"));
                        }
                    }
                )
                ->WHERE("active_trade_licences.is_parked", FALSE)
                ->WHERE("active_trade_licences.ulb_id", $ulbId)
                ->groupBy(["users_role.user_id", "users_role.user_name", "users_role.wf_role_id", "users_role.role_name"]);
                if (!in_array($roleId, [8])) 
                {
                    $data  = $data->WHEREIN("active_trade_licences.payment_status", [1,2]);
                }
                if (in_array($roleId, [8])) 
                {
                    $data  = $data->WHERE(function ($where) {
                                $where->WHERE("active_trade_licences.payment_status", "=", 0)
                                    ->ORWHERENULL("active_trade_licences.payment_status");
                            });
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
            return responseMsgs(true, $header, $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function userWiseWardWiseLevelPending(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $roleId = $roleId2 = $userId = null;
            $header = $userName = $roleName = null;
            $allRolse = $this->_COMMON_FUNCTION->getAllRoles($refUserId,$ulbId,$this->_WF_MASTER_Id,0,TRUE);
            $mWardPermission = collect([]);
            if ($request->ulbId) 
            {
                $ulbId = $request->ulbId;
            }
            if ($request->roleId) 
            {
                $roleId = $request->roleId;
                $currentRole = (array_values(collect($allRolse)->where("id",$roleId)->toArray()))[0]??[];
                
                $roleName = $currentRole["role_name"]??"";
            }
            if ($request->userId) 
            {
                $userId = $request->userId;
                $userName = DB::TABLE('users')->find($userId )->name??"";
                $roleId2 = ($this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_WF_MASTER_Id))->role_id ?? 0;
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) 
            {
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2 ? $roleId2 : $roleId;
            if (!in_array($roleId, [11, 8])) 
            {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }
            if($roleName)
            {
                $header = ["header"=>"PENDING AT $roleName"];
            }
            if($userName)
            {
                $header = ["header"=>"PENDING AT $userName"];
            }
            
            $mWardIds = $mWardPermission->implode("ward_id", ",");
            $mWardIds = explode(',', ($mWardIds ? $mWardIds : "0"));
            $data = UlbWardMaster::SELECT(
                DB::RAW(" DISTINCT(ward_name) as ward_no,ulb_ward_masters.id AS ward_id, COUNT(active_trade_licences.id) AS total")
            )
                ->LEFTJOIN("active_trade_licences", "ulb_ward_masters.id", "active_trade_licences.ward_id");
            if ($roleId == 8) 
            {
                $data = $data->LEFTJOIN("wf_roles", "wf_roles.id", "active_trade_licences.current_role")
                    ->WHERENOTNULL("active_trade_licences.user_id")
                    ->WHERE(function ($where) {
                        $where->WHERE("active_trade_licences.payment_status", "=", 0)
                            ->ORWHERENULL("active_trade_licences.payment_status");
                    });
            } 
            else 
            {
                $data = $data->JOIN("wf_roles", "wf_roles.id", "active_trade_licences.current_role")
                    ->WHERE("wf_roles.id", $roleId);
            }

            if (!in_array($roleId, [11, 8]) && $userId) 
            {
                $data = $data->WHEREIN("active_trade_licences.ward_id", $mWardIds);
            }
            $data = $data->WHERE("active_trade_licences.ulb_id", $ulbId)
                    ->WHERE("active_trade_licences.is_parked", FALSE);
            $data = $data->groupBy(["ward_name","ulb_ward_masters.id"]);

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
            return responseMsgs(true,$header, $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function levelformdetail(Request $request)
    {
        # $roleId =8 jsk , $roleId =11 back office
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $roleId = $roleId2 = $userId = null;
            $mWardPermission = collect([]);
            
            if ($request->ulbId) 
            {
                $ulbId = $request->ulbId;
            }
            if ($request->roleId) 
            {
                $roleId = $request->roleId;
            }
            if ($request->userId) 
            {
                $userId = $request->userId;
                $roleId2 = ($this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_WF_MASTER_Id))->role_id ?? 0;
            }
            if (($request->roleId && $request->userId) && ($roleId != $roleId2)) 
            {
                // dd($roleId,$roleId2,$this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_WF_MASTER_Id),DB::getQueryLog());
                throw new Exception("Invalid RoleId Pass");
            }
            $roleId = $roleId2 ? $roleId2 : $roleId;
            if (!in_array($roleId, [11, 8])) 
            {
                $mWfWardUser = new WfWardUser();
                $mWardPermission = $mWfWardUser->getWardsByUserId($userId);
            }
            $mWardIds = $mWardPermission->implode("ward_id", ",");
            if($request->wardId)
            {
                $mWardIds = $request->wardId; 
            }
            $mWardIds = explode(',', ($mWardIds ? $mWardIds : "0"));
            
            // DB::enableQueryLog();
            $data = ActiveTradeLicence::SELECT(
                    DB::RAW("wf_roles.id AS role_id, wf_roles.role_name,
                        active_trade_licences.id, active_trade_licences.application_no, active_trade_licences.address,
                        ward_name as ward_no, 
                        owner.owner_name, owner.mobile_no")
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_licences.ward_id")
                ->LEFTJOIN(DB::RAW("( 
                                SELECT DISTINCT(active_trade_owners.temp_id) AS temp_id,STRING_AGG(owner_name,',') AS owner_name, 
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no
                                FROM active_trade_owners
                                JOIN active_trade_licences ON active_trade_licences.id = active_trade_owners.temp_id
                                WHERE active_trade_owners.is_active = true 
                                    AND active_trade_licences.ulb_id = $ulbId
                                GROUP BY active_trade_owners.temp_id
                                ) AS owner
                                "), function ($join) {
                    $join->on("owner.temp_id", "=", "active_trade_licences.id");
                });
            if ($roleId == 8) 
            {
                $data = $data->LEFTJOIN("wf_roles", "wf_roles.id", "active_trade_licences.current_role")
                    ->WHERENOTNULL("active_trade_licences.user_id")
                    ->WHERE(function ($where) {
                        $where->WHERE("active_trade_licences.payment_status", "=", 0)
                            ->ORWHERENULL("active_trade_licences.payment_status");
                    });
            } 
            else 
            {
                $data = $data->JOIN("wf_roles", "wf_roles.id", "active_trade_licences.current_role")
                    ->WHERE("wf_roles.id", $roleId);
            }
            $data = $data->WHERE("active_trade_licences.ulb_id", $ulbId)
                    ->WHERE("active_trade_licences.is_parked", FALSE);
            if (!in_array($roleId, [11, 8]) && $userId) 
            {
                $data = $data->WHEREIN("active_trade_licences.ward_id", $mWardIds);
            }
            if($request->wardId)
            {
                $data = $data->WHEREIN("active_trade_licences.ward_id", $mWardIds);
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
        } 
        catch (Exception $e) 
        {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    public function bulkPaymentRecipt(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->userId)
            {
                $userId = $request->userId;
            }
            $active = ActiveTradeLicence::select(
                    "active_trade_licences.id as application_id",
                    "trade_transactions.id as tran_id",
                    "active_trade_licences.application_no",
                    "active_trade_licences.provisional_license_no",
                    "active_trade_licences.license_no",
                    "active_trade_licences.firm_name",
                    "active_trade_licences.holding_no",
                    "active_trade_licences.address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    "trade_transactions.tran_no",
                    "trade_transactions.tran_type",
                    "trade_transactions.tran_date",
                    "trade_transactions.payment_mode",
                    "trade_transactions.paid_amount",
                    "trade_transactions.penalty",
                    "trade_cheque_dtls.cheque_no",
                    "trade_cheque_dtls.cheque_date",
                    "trade_cheque_dtls.bank_name",
                    "trade_cheque_dtls.branch_name",
                    "fine_rebate.delay_fee",
                    "fine_rebate.denial_fee",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type,
                            (trade_transactions.paid_amount-fine_rebate.penalty)as rate
                            ")
                )
                ->join("ulb_masters", "ulb_masters.id", "active_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_trade_licences.ward_id");
                })
                ->join("trade_transactions","trade_transactions.temp_id","active_trade_licences.id")
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->leftjoin(DB::raw("(SELECT STRING_AGG(active_trade_owners.owner_name,',') as owner_name,
                                                STRING_AGG(active_trade_owners.guardian_name,',') as guardian_name,
                                                STRING_AGG(active_trade_owners.mobile_no::text,',') as mobile,
                                                active_trade_owners.temp_id
                                            FROM active_trade_owners 
                                            JOIN trade_transactions ON trade_transactions.temp_id = active_trade_owners.temp_id 
                                            WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                                AND trade_transactions.status in(1,2)
                                                AND active_trade_owners.is_active  = TRUE
                                            GROUP BY active_trade_owners.temp_id
                                            ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "active_trade_licences.id");
                })
                ->leftjoin(DB::RAW("(SELECT trade_transactions.id AS tran_id,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Delay Apply License' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS delay_fee,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Denial Apply' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS denial_fee,
                                        SUM( trade_fine_rebetes.amount) AS penalty
                                    FROM trade_fine_rebetes
                                    JOIN trade_transactions ON trade_transactions.id = trade_fine_rebetes.tran_id 
                                    WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                        AND trade_transactions.status in(1,2)
                                        AND trade_fine_rebetes.status  = 1
                                    GROUP BY trade_transactions.id
                        ) fine_rebate"),function($join){
                            $join->on("fine_rebate.tran_id","trade_transactions.id");
                        })
                ->WHEREBETWEEN('trade_transactions.tran_date',[$fromDate,$uptoDate])
                ->WHEREIN('trade_transactions.status',[1,2]);
           
            
            $approved = TradeLicence::select(
                    "trade_licences.id as application_id",
                    "trade_transactions.id as tran_id",
                    "trade_licences.application_no",
                    "trade_licences.provisional_license_no",
                    "trade_licences.license_no",
                    "trade_licences.firm_name",
                    "trade_licences.holding_no",
                    "trade_licences.address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    "trade_transactions.tran_no",
                    "trade_transactions.tran_type",
                    "trade_transactions.tran_date",
                    "trade_transactions.payment_mode",
                    "trade_transactions.paid_amount",
                    "trade_transactions.penalty",
                    "trade_cheque_dtls.cheque_no",
                    "trade_cheque_dtls.cheque_date",
                    "trade_cheque_dtls.bank_name",
                    "trade_cheque_dtls.branch_name",
                    "fine_rebate.delay_fee",
                    "fine_rebate.denial_fee",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type,
                            (trade_transactions.paid_amount-fine_rebate.penalty)as rate
                        ")
                    
                )
                ->join("ulb_masters", "ulb_masters.id", "trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                })
                ->join("trade_transactions","trade_transactions.temp_id","trade_licences.id")
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->leftjoin(DB::raw("(SELECT STRING_AGG(trade_owners.owner_name,',') as owner_name,
                                            STRING_AGG(trade_owners.guardian_name,',') as guardian_name,
                                            STRING_AGG(trade_owners.mobile_no,',') as mobile,
                                            trade_owners.temp_id
                                        FROM trade_owners 
                                        JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id 
                                        WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                            AND trade_transactions.status in(1,2)
                                            AND trade_owners.is_active  = TRUE
                                        GROUP BY trade_owners.temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "trade_licences.id");
                })
                ->leftjoin(DB::RAW("(SELECT trade_transactions.id AS tran_id,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Delay Apply License' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS delay_fee,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Denial Apply' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS denial_fee,
                                        SUM( trade_fine_rebetes.amount) AS penalty
                                    FROM trade_fine_rebetes
                                    JOIN trade_transactions ON trade_transactions.id = trade_fine_rebetes.tran_id 
                                    WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                        AND trade_transactions.status in(1,2)
                                        AND trade_fine_rebetes.status  = 1
                                    GROUP BY trade_transactions.id
                        ) fine_rebate"),function($join){
                            $join->on("fine_rebate.tran_id","trade_transactions.id");
                        })
                ->WHEREBETWEEN('trade_transactions.tran_date',[$fromDate,$uptoDate])
                ->WHEREIN('trade_transactions.status',[1,2]);
        
            $rejected = RejectedTradeLicence::select(
                    "rejected_trade_licences.id as application_id",
                    "trade_transactions.id as tran_id",
                    "rejected_trade_licences.application_no",
                    "rejected_trade_licences.provisional_license_no",
                    "rejected_trade_licences.license_no",
                    "rejected_trade_licences.firm_name",
                    "rejected_trade_licences.holding_no",
                    "rejected_trade_licences.address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    "trade_transactions.tran_no",
                    "trade_transactions.tran_type",
                    "trade_transactions.tran_date",
                    "trade_transactions.payment_mode",
                    "trade_transactions.paid_amount",
                    "trade_transactions.penalty",
                    "trade_cheque_dtls.cheque_no",
                    "trade_cheque_dtls.cheque_date",
                    "trade_cheque_dtls.bank_name",
                    "trade_cheque_dtls.branch_name",
                    "fine_rebate.delay_fee",
                    "fine_rebate.denial_fee",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type,
                            (trade_transactions.paid_amount-fine_rebate.penalty)as rate
                        ")
                )
                ->join("ulb_masters", "ulb_masters.id", "rejected_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "rejected_trade_licences.ward_id");
                })
                ->join("trade_transactions","trade_transactions.temp_id","rejected_trade_licences.id")
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->leftjoin(DB::raw("(SELECT STRING_AGG(rejected_trade_owners.owner_name,',') as owner_name,
                                            STRING_AGG(rejected_trade_owners.guardian_name,',') as guardian_name,
                                            STRING_AGG(rejected_trade_owners.mobile_no,',') as mobile,
                                            rejected_trade_owners.temp_id
                                        FROM rejected_trade_owners 
                                        JOIN trade_transactions ON trade_transactions.temp_id = rejected_trade_owners.temp_id 
                                        WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                            AND trade_transactions.status in(1,2)
                                            AND rejected_trade_owners.is_active  = TRUE
                                        GROUP BY rejected_trade_owners.temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "rejected_trade_licences.id");
                })
                ->leftjoin(DB::RAW("(SELECT trade_transactions.id AS tran_id,
                                    SUM(CASE WHEN trade_fine_rebetes.type = 'Delay Apply License' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS delay_fee,
                                    SUM(CASE WHEN trade_fine_rebetes.type = 'Denial Apply' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS denial_fee,
                                    SUM( trade_fine_rebetes.amount) AS penalty
                                FROM trade_fine_rebetes
                                JOIN trade_transactions ON trade_transactions.id = trade_fine_rebetes.tran_id 
                                WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                    AND trade_transactions.status in(1,2)
                                    AND trade_fine_rebetes.status  = 1
                                GROUP BY trade_transactions.id
                    ) fine_rebate"),function($join){
                        $join->on("fine_rebate.tran_id","trade_transactions.id");
                })
                ->WHEREBETWEEN('trade_transactions.tran_date',[$fromDate,$uptoDate])
                ->WHEREIN('trade_transactions.status',[1,2]);
        
            $renewal = TradeRenewal::select(
                        "trade_renewals.id as application_id",
                        "trade_transactions.id as tran_id",
                        "trade_renewals.application_no",
                        "trade_renewals.provisional_license_no",
                        "trade_renewals.license_no",
                        "trade_renewals.firm_name",
                        "trade_renewals.holding_no",
                        "trade_renewals.address",
                        "owner.owner_name",
                        "owner.guardian_name",
                        "owner.mobile",
                        "trade_transactions.tran_no",
                        "trade_transactions.tran_type",
                        "trade_transactions.tran_date",
                        "trade_transactions.payment_mode",
                        "trade_transactions.paid_amount",
                        "trade_transactions.penalty",
                        "trade_cheque_dtls.cheque_no",
                        "trade_cheque_dtls.cheque_date",
                        "trade_cheque_dtls.bank_name",
                        "trade_cheque_dtls.branch_name",
                        "fine_rebate.delay_fee",
                        "fine_rebate.denial_fee",
                        DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type,
                                (trade_transactions.paid_amount-fine_rebate.penalty)as rate
                            ")
                    )
                    ->join("ulb_masters", "ulb_masters.id", "trade_renewals.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_renewals.ward_id");
                    })
                    ->join("trade_transactions","trade_transactions.temp_id","trade_renewals.id")
                    ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(trade_owners.owner_name,',') as owner_name,
                                                STRING_AGG(trade_owners.guardian_name,',') as guardian_name,
                                                STRING_AGG(trade_owners.mobile_no,',') as mobile,
                                                trade_owners.temp_id
                                            FROM trade_owners 
                                            JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id 
                                            WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                                AND trade_transactions.status in(1,2)
                                                AND trade_owners.is_active  = TRUE
                                            GROUP BY trade_owners.temp_id
                                            ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_renewals.id");
                    })
                    ->leftjoin(DB::RAW("(SELECT trade_transactions.id AS tran_id,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Delay Apply License' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS delay_fee,
                                        SUM(CASE WHEN trade_fine_rebetes.type = 'Denial Apply' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS denial_fee,
                                        SUM( trade_fine_rebetes.amount) AS penalty
                                    FROM trade_fine_rebetes
                                    JOIN trade_transactions ON trade_transactions.id = trade_fine_rebetes.tran_id 
                                    WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                        AND trade_transactions.status in(1,2)
                                        AND trade_fine_rebetes.status  = 1
                                    GROUP BY trade_transactions.id
                        ) fine_rebate"),function($join){
                            $join->on("fine_rebate.tran_id","trade_transactions.id");
                    })
                    ->WHEREBETWEEN('trade_transactions.tran_date',[$fromDate,$uptoDate])
                    ->WHEREIN('trade_transactions.status',[1,2]);
            if($wardId)
            {
                $active = $active->WHERE('ulb_ward_masters.id',$wardId);
                $approved = $approved->WHERE('ulb_ward_masters.id',$wardId);
                $rejected = $rejected->WHERE('ulb_ward_masters.id',$wardId);
                $renewal = $renewal->WHERE('ulb_ward_masters.id',$wardId);
            }
            if($userId)
            {
                $active = $active->WHERE('trade_transactions.emp_dtl_id',$userId);
                $approved = $approved->WHERE('trade_transactions.emp_dtl_id',$userId);
                $rejected = $rejected->WHERE('trade_transactions.emp_dtl_id',$userId);
                $renewal = $renewal->WHERE('trade_transactions.emp_dtl_id',$userId);
            }
            if($ulbId)
            {
                $active = $active->WHERE('active_trade_licences.ulb_id',$ulbId);
                $approved = $approved->WHERE('trade_licences.ulb_id',$ulbId);
                $rejected = $rejected->WHERE('rejected_trade_licences.ulb_id',$ulbId);
                $renewal = $renewal->WHERE('trade_renewals.ulb_id',$ulbId);
            }
            $data = $active->union($approved)
                    ->union($rejected)
                    ->union($renewal)
                    ->get();
            foreach ($data as $key => $val) 
            {
                $data[$key]["paid_amount_in_words"] = getIndianCurrency($val->paid_amount);
            }            
            $data = remove_null($data);            
            return responseMsg(true, "", $data);
        }
        catch (Exception $e) 
        {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
    public function applicationStatus(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $wardId = null;
            $userId = null;
            $status = "";
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
            if($request->ulbId)
            {
                $ulbId = $request->ulbId;
            }
            if($request->userId)
            {
                $userId = $request->userId;
            }
            $active = ActiveTradeLicence::select(
                "active_trade_licences.id as application_id",
                "active_trade_licences.application_no",
                "active_trade_licences.provisional_license_no",
                "active_trade_licences.license_no",
                "active_trade_licences.firm_name",
                "active_trade_licences.holding_no",
                "active_trade_licences.address",
                "active_trade_licences.application_date",
                "active_trade_licences.valid_from",
                "active_trade_licences.valid_upto",
                "active_trade_licences.application_type_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                        ")
                )
                ->join("ulb_masters", "ulb_masters.id", "active_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_trade_licences.ward_id");
                })
                ->leftjoin(DB::raw("(SELECT STRING_AGG(active_trade_owners.owner_name,',') as owner_name,
                                                STRING_AGG(active_trade_owners.guardian_name,',') as guardian_name,
                                                STRING_AGG(active_trade_owners.mobile_no::text,',') as mobile,
                                                active_trade_owners.temp_id
                                            FROM active_trade_owners
                                            JOIN active_trade_licences ON active_trade_licences.id = active_trade_owners.temp_id
                                            WHERE active_trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                                                AND active_trade_licences.is_active = TRUE
                                                AND active_trade_owners.is_active  = TRUE
                                            GROUP BY active_trade_owners.temp_id
                                            ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "active_trade_licences.id");
                })
                ->WHEREBETWEEN('active_trade_licences.application_date',[$fromDate,$uptoDate])
                ->WHERE('active_trade_licences.is_active',TRUE);
       
        
            $approved = TradeLicence::select(
                    "trade_licences.id as application_id",
                    "trade_licences.application_no",
                    "trade_licences.provisional_license_no",
                    "trade_licences.license_no",
                    "trade_licences.firm_name",
                    "trade_licences.holding_no",
                    "trade_licences.address",
                    "trade_licences.application_date",
                    "trade_licences.valid_from",
                    "trade_licences.valid_upto",
                    "trade_licences.application_type_id",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                        ")
                    
                )
                ->join("ulb_masters", "ulb_masters.id", "trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                })
                ->leftjoin(DB::raw("(SELECT STRING_AGG(trade_owners.owner_name,',') as owner_name,
                                            STRING_AGG(trade_owners.guardian_name,',') as guardian_name,
                                            STRING_AGG(trade_owners.mobile_no,',') as mobile,
                                            trade_owners.temp_id
                                        FROM trade_owners 
                                        JOIN trade_licences ON trade_licences.id = trade_owners.temp_id 
                                        WHERE trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                                            AND trade_licences.is_active  = TRUE
                                            AND trade_owners.is_active  = TRUE
                                        GROUP BY trade_owners.temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "trade_licences.id");
                })
                ->WHEREBETWEEN('trade_licences.application_date',[$fromDate,$uptoDate])
                ->WHERE('trade_licences.is_active',TRUE);
        
            $rejected = RejectedTradeLicence::select(
                    "rejected_trade_licences.id as application_id",
                    "rejected_trade_licences.application_no",
                    "rejected_trade_licences.provisional_license_no",
                    "rejected_trade_licences.license_no",
                    "rejected_trade_licences.firm_name",
                    "rejected_trade_licences.holding_no",
                    "rejected_trade_licences.address",
                    "rejected_trade_licences.application_date",
                    "rejected_trade_licences.valid_from",
                    "rejected_trade_licences.valid_upto",
                    "rejected_trade_licences.application_type_id",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                        ")
                )
                ->join("ulb_masters", "ulb_masters.id", "rejected_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "rejected_trade_licences.ward_id");
                })                
                ->leftjoin(DB::raw("(SELECT STRING_AGG(rejected_trade_owners.owner_name,',') as owner_name,
                                            STRING_AGG(rejected_trade_owners.guardian_name,',') as guardian_name,
                                            STRING_AGG(rejected_trade_owners.mobile_no,',') as mobile,
                                            rejected_trade_owners.temp_id
                                        FROM rejected_trade_owners 
                                        JOIN rejected_trade_licences ON rejected_trade_licences.id = rejected_trade_owners.temp_id 
                                        WHERE rejected_trade_licences.application_date BETWEEN '$fromDate' AND '$uptoDate'
                                            AND rejected_trade_licences.is_active  = TRUE
                                            AND rejected_trade_owners.is_active  = TRUE
                                        GROUP BY rejected_trade_owners.temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "rejected_trade_licences.id");
                })                
                ->WHEREBETWEEN('rejected_trade_licences.application_date',[$fromDate,$uptoDate])
                ->WHERE('rejected_trade_licences.is_active',TRUE);
            /*
            $renewal = TradeRenewal::select(
                    "trade_renewals.id as application_id",
                    "trade_transactions.id as tran_id",
                    "trade_renewals.application_no",
                    "trade_renewals.provisional_license_no",
                    "trade_renewals.license_no",
                    "trade_renewals.firm_name",
                    "trade_renewals.holding_no",
                    "trade_renewals.address",
                    "trade_renewals.application_date",
                    "trade_renewals.application_type_id",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    "trade_transactions.tran_no",
                    "trade_transactions.tran_type",
                    "trade_transactions.tran_date",
                    "trade_transactions.payment_mode",
                    "trade_transactions.paid_amount",
                    "trade_transactions.penalty",
                    "trade_cheque_dtls.cheque_no",
                    "trade_cheque_dtls.cheque_date",
                    "trade_cheque_dtls.bank_name",
                    "trade_cheque_dtls.branch_name",
                    "fine_rebate.delay_fee",
                    "fine_rebate.denial_fee",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type,
                            (trade_transactions.paid_amount-fine_rebate.penalty)as rate
                        ")
                )
                ->join("ulb_masters", "ulb_masters.id", "trade_renewals.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "trade_renewals.ward_id");
                })
                ->join("trade_transactions","trade_transactions.temp_id","trade_renewals.id")
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->leftjoin(DB::raw("(SELECT STRING_AGG(trade_owners.owner_name,',') as owner_name,
                                            STRING_AGG(trade_owners.guardian_name,',') as guardian_name,
                                            STRING_AGG(trade_owners.mobile_no,',') as mobile,
                                            trade_owners.temp_id
                                        FROM trade_owners 
                                        JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id 
                                        WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                            AND trade_transactions.status in(1,2)
                                            AND trade_owners.is_active  = TRUE
                                        GROUP BY trade_owners.temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "trade_renewals.id");
                })
                ->leftjoin(DB::RAW("(SELECT trade_transactions.id AS tran_id,
                                    SUM(CASE WHEN trade_fine_rebetes.type = 'Delay Apply License' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS delay_fee,
                                    SUM(CASE WHEN trade_fine_rebetes.type = 'Denial Apply' THEN  trade_fine_rebetes.amount ELSE 0 END ) AS denial_fee,
                                    SUM( trade_fine_rebetes.amount) AS penalty
                                FROM trade_fine_rebetes
                                JOIN trade_transactions ON trade_transactions.id = trade_fine_rebetes.tran_id 
                                WHERE trade_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                                    AND trade_transactions.status in(1,2)
                                    AND trade_fine_rebetes.status  = 1
                                GROUP BY trade_transactions.id
                    ) fine_rebate"),function($join){
                        $join->on("fine_rebate.tran_id","trade_transactions.id");
                })
                ->WHEREBETWEEN('trade_transactions.tran_date',[$fromDate,$uptoDate])
                ->WHEREIN('trade_transactions.status',[1,2]);
            */
            if($wardId)
            {
                $active = $active->WHERE('ulb_ward_masters.id',$wardId);
                $approved = $approved->WHERE('ulb_ward_masters.id',$wardId);
                $rejected = $rejected->WHERE('ulb_ward_masters.id',$wardId);
                // $renewal = $renewal->WHERE('ulb_ward_masters.id',$wardId);
            }
            if($userId)
            {
                $active = $active->WHERE('active_trade_licences.user_id',$userId);
                $approved = $approved->WHERE('trade_licences.user_id',$userId);
                $rejected = $rejected->WHERE('rejected_trade_licences.user_id',$userId);
                // $renewal = $renewal->WHERE('trade_renewals.user_id',$userId);
            }
            if($ulbId)
            {
                $active = $active->WHERE('active_trade_licences.ulb_id',$ulbId);
                $approved = $approved->WHERE('trade_licences.ulb_id',$ulbId);
                $rejected = $rejected->WHERE('rejected_trade_licences.ulb_id',$ulbId);
                // $renewal = $renewal->WHERE('trade_renewals.ulb_id',$ulbId);
            }
            $data = collect([]);

            switch($request->status)
            {
                #approved
                case 1 : $data=$approved;
                        break;
                #PENDING
                case 2 : $data=$active;
                        break;
                #rejected
                case 3 : $data=$rejected;
                        break;
                #PAYMENT DONE BUT DOCUMENT NOT UPLOADED
                case 4 : $data=$active->WHEREIN("payment_status",[1,2])->WHERE("document_upload_status","<>",1);
                        break;
                #DOCUMENT UPLOADED BUT PAYMENT NOT DONE
                case 5 : $data=$active->WHERENOTIN("payment_status",[1,2])->WHERE("document_upload_status",1);
                        break;
                #PAYMENT AND DOCUMENT UPLOAD PENDING
                case 6 : $data=$active->WHERENOTIN("payment_status",[1,2])->WHERE("document_upload_status","<>",1);
                        break;
                default : $data = $active->union($approved)->union($rejected);
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
            return responseMsgs(true, "", remove_null($list), $apiId, $version, $queryRunTime, $action, $deviceId);

        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
}
