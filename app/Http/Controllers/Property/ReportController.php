<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ReportController extends Controller
{
    use Auth;    
    
    private $Repository;
    private $_common;
    public function __construct(IReport $TradeRepository)
    {
        $this->Repository = $TradeRepository;
        $this->_common = new CommonFunction();
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
    public function levelwisependingform(Request $request)
    {
        $request->request->add(["metaData"=>["pr4.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->levelwisependingform($request);
    }
    public function levelformdetail(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "roleId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["pr4.2",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->levelformdetail($request);
    }
    public function userWiseLevelPending(Request $request)
    {
        $request->validate(
            [
                "userId" => "required|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["pr4.2.1",1.1,null,$request->getMethod(),null,]]);

        $refUser        = Auth()->user();
        $refUserId      = $refUser->id;
        $ulbId          = $refUser->ulb_id;
        $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        if($request->ulbId)
        {
            $ulbId = $request->ulbId;
        }
        
        $respons =  $this->levelformdetail($request);
        $metaData= collect($request->metaData)->all();        
        list($apiId, $version, $queryRunTime,$action,$deviceId)=$metaData;

        $roles = ($this->_common->getUserRoll($request->userId,$ulbId,$safWorkFlow));
        $respons = json_decode(json_encode($respons), true);
        if($respons["original"]["status"])
        {
            $respons["original"]["data"]["items"] = collect($respons["original"]["data"]["items"])->map(function($val) use($roles){
                                                        $val["role_name"] = $roles->role_name??"";
                                                        $val["role_id"] = $roles->role_id??0;
                                                        return $val;
                                                    });

        }
        return responseMsgs($respons["original"]["status"], $respons["original"]["message"], $respons["original"]["data"],$apiId, $version, $queryRunTime,$action,$deviceId);
    }
    public function userWiseWardWireLevelPending(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "required|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage"=>"nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["pr4.2.1.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->userWiseWardWireLevelPending($request);
    }

    public function safSamFamGeotagging(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData"=>["pr5.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->safSamFamGeotagging($request);
    }
}