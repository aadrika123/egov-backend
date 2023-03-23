<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;
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
                "licenseStatus"=>"nullable|in:EXPIRED,VALID",     
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
        $request->request->add(["metaData" => ["pr10.2.1.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->userWiseWardWiseLevelPending($request);
    }
}
