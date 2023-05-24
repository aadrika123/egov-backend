<?php

namespace App\Http\Controllers\Mdm;

use App\Http\Controllers\Controller;
use App\Models\Trade\TradeParamFirmType;
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
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_FIRM_TYPE = NEW TradeParamFirmType();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
    }

    public function addFirmType(Request $request)
    {
        try{
            $sms = "";
            $request->validate(["firmType" => "required|unique:trade_param_firm_types|regex:/^[a-zA-Z0-9][a-zA-Z0-9\'\.\-\,\&\s\/]+$/i"]);
            $firmData = $this->_FIRM_TYPE->SELECT("*")
                        ->WHERE(DB::RAW("UPPER(firm_type)"),trim(strtoupper($request->firmType)))
                        ->ORDERBY("id")
                        ->FIRST();

            DB::beginTransaction();                        
            if($firmData->isEmpty())
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
            DB::rollBack();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    public function firmtypelist()
    {
        try{

            $list = $this->_FIRM_TYPE->select("*")
                    ->get();
            return responseMsg(true,["heard"=>"Firm Type List"],$list);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),[]);
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
                    "status"=>"nullable|in:1,0"
                ]
            );
            $firmData = $this->_FIRM_TYPE->find($request->id);
            DB::beginTransaction();                        
            if($firmData->isEmpty())
            {
                  throw new Exception("Data Not Found");   
            }
             #update data
            $sms="Updated Recode";
            $firmData->firm_type     =  trim(strtoupper($request->firmType));
            if(!empty($request->firmType))
            {
                $firmData->status        = 1;
            }
            
            $firmData->update();
            DB::rollBack();
            return responseMsg(true,$sms,"");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

}