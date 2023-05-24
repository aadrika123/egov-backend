<?php

namespace App\Http\Controllers\Mdm;

use App\Http\Controllers\Controller;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Repository\Common\CommonFunction;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class TradeController extends Controller
{
    protected $_FIRM_TYPE;
    protected $_APPLICATION_TYPE;
    protected $_CATEGORY_TYPE;
    protected $_ITEM_TYPE;
    protected $_RATES;
    protected $_OWNERSHIP_TYPE;
    protected $_COMMON_FUNCTION;
    protected $_REPOSITORY;
    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;

    public function __construct()
    {
        DB::enableQueryLog();
        $this->_COMMON_FUNCTION     = new CommonFunction();
        $this->_FIRM_TYPE           = new TradeParamFirmType();
        $this->_APPLICATION_TYPE    = new TradeParamApplicationType();
        $this->_CATEGORY_TYPE       = new TradeParamCategoryType();
        $this->_ITEM_TYPE           = new TradeParamItemType();
        $this->_RATES               = new TradeParamLicenceRate();
        $this->_OWNERSHIP_TYPE      = new TradeParamOwnershipType();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
    }

    #============= Firm Type Crud =================
    public function addFirmType(Request $request)
    {
        try{
            $sms = "";
            $request->validate(["firmType" => "required|regex:/^[a-zA-Z0-9][a-zA-Z0-9\'\.\-\,\&\s\/]+$/i"]);
            
            $firmData = $this->_FIRM_TYPE->SELECT("*")
                        ->WHERE(DB::RAW("UPPER(firm_type)"),trim(strtoupper($request->firmType)))
                        ->ORDERBY("id")
                        ->FIRST();
            
            DB::beginTransaction();                        
            if(!$firmData)
            {
                #insert Data
                $sms="New Recode Added";  
                $newFirmType                =  $this->_FIRM_TYPE;
                $newFirmType->firm_type     =  trim(strtoupper($request->firmType));
                $newFirmType->status        =  1;
                $newFirmType->save();       
            }
            else
            {
                #update data
                $sms="Updated Recode";
                $firmData->status       = 1;
                $firmData->update();
            }
            DB::commit();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    public function firmTypeList()
    {
        try{

            $list = $this->_FIRM_TYPE->select("*")
                    ->get();
            return responseMsg(true,["heard"=>"Firm Type List"],remove_null($list));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),[]);
        }
    }

    public function firmType(Request $request)
    {
        try{
            $request->validate(
                [
                    "id" => "required|digits_between:1,9223372036854775807",
                ]
            );
            $firmData = $this->_FIRM_TYPE->find($request->id);
            if(!$firmData)
            {
                  throw new Exception("Data Not Found");   
            }
            return responseMsg(true,["heard"=>"Firm Type Detail"],remove_null($firmData));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }

    public function updateFirmType(Request $request)
    {

        try{
            $sms = "";
            $request->validate(
                [
                    "firmType" => "required|regex:/^[a-zA-Z0-9][a-zA-Z0-9\'\.\-\,\&\s\/]+$/i",
                    "id" => "required|digits_between:1,9223372036854775807",
                    "status"=>"nullable|in:0,1"
                ]
            );
            $firmData = $this->_FIRM_TYPE->find($request->id);
            
            DB::beginTransaction();                        
            if(!$firmData)
            {
                  throw new Exception("Data Not Found");   
            }
             #update data
            $sms="Updated Recode";
            $firmData->firm_type     =  trim(strtoupper($request->firmType));
            if(isset($request->status))
            {
                switch($request->status)
                {
                    case 0 : $firmData->status   = 0;
                            break;
                    case 1  : $firmData->status  = 1;
                            break;
                }

            }
            
            $firmData->update();
            DB::commit();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    #============= End Firm Type Crud =================
    #============= Application Type Crud =================
    public function addApplicationType(Request $request)
    {
        try{
            $sms = "";
            $request->validate(["applicationType" => "required|regex:/^[a-zA-Z0-9][a-zA-Z0-9\'\.\-\,\&\s\/]+$/i"]);
            
            $appData = $this->_APPLICATION_TYPE->SELECT("*")
                        ->WHERE(DB::RAW("UPPER(application_type)"),trim(strtoupper($request->applicationType)))
                        ->ORDERBY("id")
                        ->FIRST();
            
            DB::beginTransaction();                        
            if(!$appData)
            {
                #insert Data
                $sms="New Recode Added";  
                $newApplicationType                   =  $this->_APPLICATION_TYPE;
                $newApplicationType->application_type =  trim(strtoupper($request->firmType));
                $newApplicationType->status           =  1;
                $newApplicationType->save();       
            }
            else
            {
                #update data
                $sms="Updated Recode";
                $appData->status       = 1;
                $appData->update();
            }
            DB::commit();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    public function applicationTypeList()
    {
        try{

            $list = $this->_APPLICATION_TYPE->select("*")
                    ->get();
            return responseMsg(true,["heard"=>"Application Type List"],remove_null($list));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),[]);
        }
    }

    public function applicationType(Request $request)
    {
        try{
            $request->validate(
                [
                    "id" => "required|digits_between:1,9223372036854775807",
                ]
            );
            $appData = $this->_APPLICATION_TYPE->find($request->id);
            if(!$appData)
            {
                  throw new Exception("Data Not Found");   
            }
            return responseMsg(true,["heard"=>"Firm Type Detail"],remove_null($appData));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }

    public function updateapplicationType(Request $request)
    {

        try{
            $sms = "";
            $request->validate(
                [
                    "applicationType" => "required|regex:/^[a-zA-Z0-9][a-zA-Z0-9\'\.\-\,\&\s\/]+$/i",
                    "id" => "required|digits_between:1,9223372036854775807",
                    "status"=>"nullable|in:0,1"
                ]
            );
            $appData = $this->_APPLICATION_TYPE->find($request->id);
            
            DB::beginTransaction();                        
            if(!$appData)
            {
                  throw new Exception("Data Not Found");   
            }
             #update data
            $sms="Updated Recode";
            $appData->application_type     =  trim(strtoupper($request->applicationType));
            if(isset($request->status))
            {
                switch($request->status)
                {
                    case 0 : $appData->status   = 0;
                            break;
                    case 1  : $appData->status  = 1;
                            break;
                }

            }
            
            $appData->update();
            DB::commit();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    #============= End Application Type Crud =================
    #============= Category Type Crud =================
    #============= End Category Type Crud =================

    #============= Item Type Crud =================
    #============= End Item Type Crud =================
    #============= Rate Crud =================
    #============= End Rate Crud =================
    #============= Ownership Type Crud =================
    #============= End Ownership Type Crud =================
}