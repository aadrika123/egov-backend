<?php

namespace App\Http\Controllers\Notice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\Add;
use App\Models\ModuleMaster;
use App\Models\Notice\NoticeTypeMaster;
use App\Repository\Common\CommonFunction;
use App\Repository\Notice\INotice;
use Illuminate\Http\Request;
use App\Traits\Auth;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Application extends Controller
{
    /**
     * Created By Sandeep Bara
     * Date 2023-03-027
     * Notice Module
     */

     use Auth;    
    
    private $_REPOSITORY;
    private $_COMMON_FUNCTION;
    protected $_GENERAL_NOTICE_WF_MASTER_Id;
    protected $_PAYMENT_NOTICE_WF_MASTER_Id;
    protected $_ILLEGAL_OCCUPATION_WF_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_NOTICE_CONSTAINT;
    protected $_MODULE_CONSTAINT;
    protected $_NOTICE_TYPE;
    public function __construct(INotice $Repository)
    {
        DB::enableQueryLog();
        $this->_REPOSITORY = $Repository;
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_GENERAL_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.GENERAL_NOTICE_MASTER_ID');
        $this->_PAYMENT_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.PAYMENT_NOTICE_MASTER_ID');
        $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id = Config::get('workflow-constants.ILLEGAL_OCCUPATION_NOTICE_MASTER_ID');
        $this->_MODULE_ID = Config::get('module-constants.NOTICE_MASTER_ID');
        $this->_MODULE_CONSTAINT = Config::get('module-constants');
        $this->_NOTICE_CONSTAINT = Config::get("NoticeConstaint");
        $this->_REF_TABLE = $this->_NOTICE_CONSTAINT["NOTICE_REF_TABLE"];
        $this->_NOTICE_TYPE = $this->_NOTICE_CONSTAINT["NOTICE-TYPE"]??null;
    }

    public function noticeType(Request $request)
    {
        try{
            $data= NoticeTypeMaster::select("id","notice_type")
                    ->where("status",1)
                    ->get();
            return responseMsg(true, "", $data);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }

    }

    public function serApplication(Request $request)
    {
        
        try{
            $request->validate(
                [
                    "moduleId"=>"required|digits_between:1,6",
                    "value"=>"required",
                    "searchBy"=>"required",
                ]
            );
            $bearerToken = (collect(($request->headers->all())['authorization']??"")->first());
            $contentType = (collect(($request->headers->all())['content-type']??"")->first());
            $data = Http::withHeaders(
                    [
                        "Authorization" => "Bearer $bearerToken",
                        "contentType" => "$contentType",    
                    ]
                );
            $url = null;
            $key = null;
            $moduleId = null;
            $moduleType = null;
            
            if($request->moduleId==1)#property
            {
                $moduleId = $this->_MODULE_CONSTAINT["PROPERTY_MODULE_ID"];
                $moduleType = "PROPERTY";
                if(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "holdingNo";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "mobileNo";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "ownerName";
                }
                else{
                    $key = "address";
                }
                $url=("http://192.168.0.165:8008/api/property/get-filter-property-details");
                $request->request->add(["filteredBy"=>"$key","parameter"=>$request->value]);
            }
            if($request->moduleId==2)#water
            {
                $moduleId = $this->_MODULE_CONSTAINT["WATER_MODULE_ID"];
                $moduleType = "WATER CONSUMER";
                if(strtoupper($request->searchBy)=="CONSUMER")
                {
                    $key = "consumerNo";
                }
                elseif(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "holdingNo";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "mobileNo";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "applicantName";
                }
                else{
                    $key = "safNo";
                }
                $url=("http://192.168.0.165:8008/api/water/search-consumer");
                $request->request->add(["filterBy"=>"$key","parameter"=>$request->value]);
            }
            if($request->moduleId==3)#trade
            {
                $moduleId = $this->_MODULE_CONSTAINT["TRADE_MODULE_ID"];
                $moduleType = "TRADE LICENSE";
                if(strtoupper($request->searchBy)=="LICENSE")
                {
                    $key = "LICENSE";
                }
                elseif(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "HOLDING";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "MOBILE";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "OWNER";
                }
                else{
                    $key = "APPLICATION";
                }
                $url=("http://192.168.0.165:8008/api/trade/application/list");
                $request->request->add(["entityName"=>"$key","entityValue"=>$request->value]);
            }
            
            // if($request->moduleId==4)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            // if($request->moduleId==5)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            // if($request->moduleId==6)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            $response =  $data->post($url,$request->all());
            $responseBody = json_decode($response->getBody());
            foreach($responseBody->data as $key=>$val)
            {
                $responseBody->data[$key]->moduleId = $moduleId;
                $responseBody->data[$key]->moduleType = $moduleType;
            }
            return($responseBody);            
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        } 
    }

    public function add(Add $request)
    {
        try {  
            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $role1 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_GENERAL_NOTICE_WF_MASTER_Id);
            $role2 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_PAYMENT_NOTICE_WF_MASTER_Id);
            $role3 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
            
            if (!$role1 && !$role2 && !$role3) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $userType1 = $this->_COMMON_FUNCTION->userType($this->_GENERAL_NOTICE_WF_MASTER_Id);
            $userType2 = $this->_COMMON_FUNCTION->userType($this->_PAYMENT_NOTICE_WF_MASTER_Id);
            $userType3 = $this->_COMMON_FUNCTION->userType($this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
            // if (!in_array(strtoupper($userType1), ["TC", "UTC"]) && !in_array(strtoupper($userType2), ["TC", "UTC"]) && !in_array(strtoupper($userType3), ["TC", "UTC"])) 
            // {
            //     throw new Exception("You Are Not Authorize For Apply Denial");
            // }            
            return $this->_REPOSITORY->add($request);
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function noticeList(Request $request)
    {
        try{
            $request->validate(
                [
                    "moduleName"=>"required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9\.\,\_\-\']+)*$/",
                ]
            );
            return $this->_REPOSITORY->noticeList($request);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
        
    }
    
}
