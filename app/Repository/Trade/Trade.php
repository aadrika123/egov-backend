<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\EloquentModels\Trade\ModelApplication;
use App\EloquentModels\Trade\ModelApplicationType;
use App\EloquentModels\Trade\ModelCategoryType;
use App\EloquentModels\Trade\ModelFirmType;
use App\EloquentModels\Trade\ModelOwnershipType;
use App\EloquentModels\Trade\ModelTradeItem;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\UlbWardMaster;
use App\Models\User;
use Illuminate\Http\Request;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class Trade implements ITrade
{
    use Auth;
    use WardPermission;

    protected $user_id;
    protected $roll_id;
    protected $ulb_id;
    protected $redis;
    protected $user_data;
    protected $application_type_id;

    public function __construct()
    { 
        $this->ModelWard = new ModelWard();
    }
    public function applyApplication(Request $request)
    {
        $user = Auth()->user();
        $this->user_id = $user->id;
        $this->ulb_id = $user->ulb_id;
        $this->redis = new Redis;
        $this->user_data = json_decode($this->redis::get('user:' . $this->user_id), true);
        $this->roll_id =  $this->user_data['role_id']??($this->getUserRoll($this->user_id,'Trade','Trade')->role_id??-1);
        try
        {
            $this->application_type_id = Config::get("TradeConstaint.APPLICATION-TYPE.".$request->applicationType);
            $data = array() ;
            $rules = [];
            $message = [];
            if (in_array($this->application_type_id, ["2", "3","4"])) {
                $rules["licenceId"] = "required";
                $message["licenceId.required"] = "Old Licence Id Requird";
            }
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $request->all(), $validator->errors());
            }
            if($request->getMethod()=='GET')
            {
                $data['wardList'] = $this->ModelWard->getAllWard($this->ulb_id)->map(function($val){
                                   $val->ward_no = $val->ward_name;
                                   return $val;
                                }); 
                $data["firmTypeList"] = $this->getFirmTypeList();
                $data["ownershipTypeList"] = $this->getownershipTypeList();
                $data["categoryTypeList"] = $this->getCotegoryList();
                $data["natureOfBusiness"] = $this->gettradeitemsList();
                if(isset($request->licenceId) && $request->licenceId  && $this->application_type_id !=1)
                {
        
                }
            }
            elseif($request->getMethod()=="POST")
            {
                $rules = [];
                $message = [];
                if (in_array($this->application_type_id, ["2", "3","4"])) {
                    $rules["licenceId"] = "required";
                    $message["licenceId.required"] = "Old Licence Id Requird";
                }
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) 
                {
                    return responseMsg(false, $request->all(), $validator->errors());
                }
                DB::beginTransaction();

            }
            return responseMsg(true,"",$data);
        }
        catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());;
        }
    }
    #---------- core function for trade Application--------

    public function searchLicence(string $licence_no)
    {
        try{
            $data = ActiveLicence::select("*")
                    ->join(
                        DB::raw("(SELECT licence_id,
                                    string_agg(owner_name,',') as owner_name,
                                    string_agg(guardian_name,',') as guardian_name,
                                    string_agg(mobile,',') as mobile
                                    FROM active_licence_owners
                                    WHERE status =1
                                    GROUP BY licence_id
                                    ) owner
                                    "),
                                    function ($join) {
                                        $join->on("owner.licence_id","=",  "active_licences.id");
                                    }
                                    )
                    ->where('status',1)
                    ->where('license_no',$licence_no)
                    ->first();
            return responseMsg(true,"",$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$licence_no);
        }
        
    }
    public function getCotegoryList()
    {
        try{
            return TradeParamCategoryType::select("id","category_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getFirmTypeList()
    {
        try{
            return TradeParamFirmType::select("id","firm_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getownershipTypeList()
    {
        try{
            return TradeParamOwnershipType::select("id","ownership_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function gettradeitemsList()
    {
        try{
            return TradeParamItemType::select("id","trade_item","trade_code")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getAllApplicationType()
    {
        try
        {
            $data = TradeParamApplicationType::select("id","application_type")
            ->where('status','1')
            ->get();
            return $data;

        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }

    #-------------------- End core function of core function --------------
    
}