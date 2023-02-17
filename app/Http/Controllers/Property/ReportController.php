<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use Auth;    
    
    private $Repository;
    public function __construct(IReport $TradeRepository)
    {
        $this->Repository = $TradeRepository;
    }

    public function collectionReport(Request $request)
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
        $request->request->add(["metaData"=>["pr1.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->collectionReport($request);
    }

    public function safCollection(Request $request)
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
        $request->request->add(["metaData"=>["pr2.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->safCollection($request);
    }
    public function safPropIndividualDemandAndCollection(Request $request)
    {
        $request->validate(
            [
                "fiYear"=>"nullable|regex:/^\d{4}-\d{4}$/",
                "key"=>"nullable|regex:/^[^<>{};:.,~!?@#$%^=&*\"]*$/i",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["pr3.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->safPropIndividualDemandAndCollection($request);
    }
}