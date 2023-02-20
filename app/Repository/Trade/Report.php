<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
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
            $where = "";
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
            $active = TradeTransaction::select(
                            DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                        active_trade_licences.application_no AS application_no,
                                        (
                                            CASE WHEN active_trade_licences.license_no='' OR ctive_trade_licences.license_no IS NULL THEN 'N/A' 
                                            ELSE ctive_trade_licences.license_no END
                                        ) AS license_no,
                                        owner_detail.owner_name,
                                        owner_detail.mobile_no,
                                        active_trade_licences.firm_name AS firm_name,
                                        trade_transactions.tran_date,
                                        trade_transactions.payment_mode AS transaction_mode,
                                        trade_transactions.paid_amount,
                                        (
                                            CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
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
                    ->LEFTJOIN(DB::RAW("(
                        SELECT DISTINCT(active_trade_owners.temp_id) AS temp_id,
                            STRING_AGG('owner_name',',') AS  owner_name,
                            STRING_AGG('mobile_no::TEXT',',') AS  mobile_no
                        FROM  active_trade_owners
                        JOIN trade_transactions ON trade_transactions.temp_id
                        WHERE $where
                        ) AS owner_detail"),function($join){
                            $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                        })
                    ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id");
            
            $approved = TradeTransaction::select(
                        DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                    trade_licences.application_no AS application_no,
                                    (
                                        CASE WHEN trade_licences.license_no='' OR ctive_trade_licences.license_no IS NULL THEN 'N/A' 
                                        ELSE ctive_trade_licences.license_no END
                                    ) AS license_no,
                                    owner_detail.owner_name,
                                    owner_detail.mobile_no,
                                    trade_licences.firm_name AS firm_name,
                                    trade_transactions.tran_date,
                                    trade_transactions.payment_mode AS transaction_mode,
                                    trade_transactions.paid_amount,
                                    (
                                        CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
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
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(trade_owners.temp_id) AS temp_id,
                        STRING_AGG('owner_name',',') AS  owner_name,
                        STRING_AGG('mobile_no::TEXT',',') AS  mobile_no
                    FROM  trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id
                    WHERE $where
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id");

            $rejected = TradeTransaction::select(
                    DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                rejected_trade_licences.application_no AS application_no,
                                (
                                    CASE WHEN rejected_trade_licences.license_no='' OR ctive_rejected_trade_licences.license_no IS NULL THEN 'N/A' 
                                    ELSE ctive_rejected_trade_licences.license_no END
                                ) AS license_no,
                                owner_detail.owner_name,
                                owner_detail.mobile_no,
                                rejected_trade_licences.firm_name AS firm_name,
                                trade_transactions.tran_date,
                                trade_transactions.payment_mode AS transaction_mode,
                                trade_transactions.paid_amount,
                                (
                                    CASE WHEN users.user_name IS NOT NULL THEN users.user_name 
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
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(rejected_trade_owners.temp_id) AS temp_id,
                        STRING_AGG('owner_name',',') AS  owner_name,
                        STRING_AGG('mobile_no::TEXT',',') AS  mobile_no
                    FROM  rejected_trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id
                    WHERE $where
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id");
            $old = TradeTransaction::select(
                    DB::raw("   ulb_ward_masters.ward_name AS ward_no,
                                trade_renewals.application_no AS application_no,
                                (
                                    CASE WHEN trade_renewals.license_no='' OR ctive_trade_renewals.license_no IS NULL THEN 'N/A' 
                                    ELSE ctive_trade_renewals.license_no END
                                ) AS license_no,
                                owner_detail.owner_name,
                                owner_detail.mobile_no,
                                trade_renewals.firm_name AS firm_name,
                                trade_transactions.tran_date,
                                trade_transactions.payment_mode AS transaction_mode,
                                trade_transactions.paid_amount
                    "),
                )
                ->JOIN("trade_renewals","trade_renewals.id","trade_transactions.temp_id")
                ->LEFTJOIN(DB::RAW("(
                    SELECT DISTINCT(trade_owners.temp_id) AS temp_id,
                        STRING_AGG('owner_name',',') AS  owner_name,
                        STRING_AGG('mobile_no::TEXT',',') AS  mobile_no
                    FROM  trade_owners
                    JOIN trade_transactions ON trade_transactions.temp_id
                    WHERE $where
                    ) AS owner_detail"),function($join){
                        $join->on("owner_detail.temp_id","trade_transactions.temp_id");
                    })
                ->LEFTJOIN("trade_cheque_dtls","trade_cheque_dtls.tran_id","trade_transactions.id");
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),$apiId, $version, $queryRunTime,$action,$deviceId);
        }
    }
}
