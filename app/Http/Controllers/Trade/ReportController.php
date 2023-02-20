<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportControlle extends Controller
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
            ]
        );
        $request->request->add(["metaData"=>["tr1.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->CollectionReports($request);
    }
}
