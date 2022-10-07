<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\EloquentModels\Trade\ModelApplicationType;
use App\Models\UlbWardMaster;
use Illuminate\Http\Request;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

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

    }
    public function applyApplication(Request $request)
    {
        $this->application_type_id = Config::get("TradeConstaint.APPLICATION-TYPE.".$request->applicationType);
        $data = array() ;
        if($request->getMethod()=='GET')
        {
            $data['ward_list'] = $this->ModelWard->getAllWard($this->ulb_id)->map(function($val){
                               $val->ward_no = $val->ward_name;
                               return $val;
                            }); 
            $data["firmtypelist"] = $this->tradefirmtypemstrmodel->getFirmTypeList();
        }
        return responseMsg(true,"",$data);
    }
    
}