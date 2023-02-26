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
}
