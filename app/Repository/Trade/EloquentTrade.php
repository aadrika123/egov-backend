<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\EloquentModels\Trade\ModelApplication;
use App\EloquentModels\Trade\ModelApplicationType;
use App\EloquentModels\Trade\ModelCategoryType;
use App\EloquentModels\Trade\ModelFirmType;
use App\EloquentModels\Trade\ModelOwnershipType;
use App\EloquentModels\Trade\ModelTradeItem;
use App\Models\UlbWardMaster;
use Illuminate\Http\Request;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class EloquentTrade implements TradeRepository
{
    use Auth;
    use WardPermission;

    protected $user_id;
    protected $roll_id;
    protected $ulb_id;
    protected $redis;
    protected $user_data;
    protected $application_type_id;

    public function __construct($user)
    {
        $this->user_id = $user->id;
        $this->ulb_id = $user->ulb_id;
        $this->redis = new Redis;
        $this->user_data = json_decode($this->redis::get('user:' . $this->user_id), true);
        $this->roll_id =  $this->user_data['role_id']??($this->getUserRoll($this->user_id,'Trade','Trade')->role_id??-1);
        $this->ModelApplicationType = new ModelApplicationType();
        $this->ModelWard = new ModelWard();
        $this->tradefirmtypemstrmodel = new ModelFirmType();
        $this->tradeownershiptypemstrmodel = new ModelOwnershipType();
        $this->cotegory = new ModelCategoryType();
        $this->tradeitemsmstrmodel = new ModelTradeItem();
        $this->application = new ModelApplication();

    }
    public function applyApplication(Request $request)
    {
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
                $data["firmtypeList"] = $this->tradefirmtypemstrmodel->getFirmTypeList();
                $data["ownershipList"] = $this->tradeownershiptypemstrmodel->getownershipTypeList();
                $data["cotegoryList"] = $this->cotegory->getCotegoryList();
                $data["natureOfBussinass"] = $this->tradeitemsmstrmodel->gettradeitemsList();
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
            return $e;
        }
    }

    public function searchLicence(string $licence_no)
    {
        $data = $this->application->searchLicence($licence_no);
        return responseMsg(true,"",$data);
    }
    
    
}