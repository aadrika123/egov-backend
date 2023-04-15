<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use Auth;    
    
    private $Repository;
    private $_common;

    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;
    public function __construct(IReport $TradeRepository)
    {
        DB::enableQueryLog();
        $this->Repository = $TradeRepository;
        $this->_common = new CommonFunction();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
    }

    public function CollectionReports(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr1.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->CollectionReports($request);
    }

    public function teamSummary (Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr2.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->teamSummary($request);
    }

    public function valideAndExpired(Request $request)
    {
        $request->validate(
            [      
                "uptoDate" => "nullable|date|date_format:Y-m-d",
                "licenseNo"=>"nullable|regex:/^[^<>{};:.,~!?@#$%^=&*\"]*$/i",
                "licenseStatus"=>"nullable|in:EXPIRED,VALID,TO BE EXPIRED",     
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr3.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->valideAndExpired($request);
    }
    public function CollectionSummary(Request $request)
    {
        $request->validate(
            [     
                "fromDate" => "nullable|date|date_format:Y-m-d",
                "uptoDate" => "nullable|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr5.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->CollectionSummary($request);
    }
    public function tradeDaseboard(Request $request)
    {
        $request->validate(
            [     
                "fiYear"=>"nullable|regex:/^\d{4}-\d{4}$/",                
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr6.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->tradeDaseboard($request);
    }
    public function ApplicantionTrackStatus(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr7.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->ApplicantionTrackStatus($request);
    }
    public function applicationAgentNotice(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr8.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->applicationAgentNotice($request);
    }
    public function noticeSummary(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["tr9.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->noticeSummary($request);
    }

    public function levelwisependingform(Request $request)
    {
        $request->request->add(["metaData" => ["tr10.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelwisependingform($request);
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
        $request->request->add(["metaData" => ["tr10.2", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelUserPending($request);
    }
    public function userWiseWardWiseLevelPending(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "required|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["tr10.2.1.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->userWiseWardWiseLevelPending($request);
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
        $request->request->add(["metaData" => ["tr10.2.2.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelformdetail($request);
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
        $request->request->add(["metaData" => ["tr10.2.2.2", 1.1, null, $request->getMethod(), null,]]);

        $refUser        = Auth()->user();
        $refUserId      = $refUser->id;
        $ulbId          = $refUser->ulb_id;
        if ($request->ulbId) {
            $ulbId = $request->ulbId;
        }

        $respons =  $this->levelformdetail($request);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        $roles = ($this->_common->getUserRoll($request->userId, $ulbId, $this->_WF_MASTER_Id));
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

    public function bulkPaymentRecipt(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["tr11.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->bulkPaymentRecipt($request);
    }
}
