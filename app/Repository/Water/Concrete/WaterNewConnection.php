<?php

namespace App\Repository\Water\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\UlbMaster;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Repository\Common\CommonFunction;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WaterNewConnection implements IWaterNewConnection
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022
    use WardPermission;

    protected $_modelWard;
    protected $_parent;
    protected $_wardNo;
    protected $_licenceId;
    protected $_shortUlbName;

    public function __construct()
    { 
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }

    public function applyApplication(Request $request)
    {
        try{
            #------------------------ Declaration-----------------------           
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $refUlbDtl          = UlbMaster::find($refUlbId);
            $refUlbName         = explode(' ',$refUlbDtl->ulb_name);
            $refNoticeDetails   = null;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $refWorkflows       = $this->_parent->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);

            $redis              = new Redis;
            $mDenialAmount      = 0; 
            $mUserData          = json_decode($redis::get('user:' . $refUserId), true);
            $mUserType          = $this->_parent->userType($refWorkflowId); 
            $mShortUlbName      = "";
            $mNowdate           = Carbon::now()->format('Y-m-d'); 
            $mTimstamp          = Carbon::now()->format('Y-m-d H:i:s'); 
            $mNoticeDate        = null;
            $mProprtyId         = null;
            $mnaturOfBusiness   = null;

            $rollId             =  $mUserData['role_id']??($this->_parent->getUserRoll($refUserId, $refUlbId,$refWorkflowId)->role_id??-1);
            $data               = array() ;
            #------------------------End Declaration-----------------------
            #---------------validation-------------------------------------
            if(!in_array(strtoupper($mUserType),["ONLINE","JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                throw new Exception("You Are Not Authorized For This Action !");
            }       
            if (!$refWorkflows) 
            {
                return responseMsg(false, "Workflow Not Available", $request->all());
            }
            elseif(!$refWorkflows['initiator'])
            {
                return responseMsg(false, "Initiator Not Available", $request->all()); 
            }
            elseif(!$refWorkflows['finisher'])
            {
                return responseMsg(false, "Finisher Not Available", $request->all()); 
            }
            #---------------End validation-------------------------
            if(in_array(strtoupper($mUserType),["ONLINE","JSK","SUPER ADMIN","TL"]))
            {
                $data['wardList'] = $this->_modelWard->getAllWard($refUlbId)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            }
            else
            {                
                $data['wardList'] = $this->_parent->WardPermission($refUserId);
            }

            if($request->getMethod()=='GET')
            {

                $data['userType']           = $mUserType;
                $data["propertyType"]       = $this->getPropertyTypeList();
                $data["ownershipTypeList"]  = $this->getOwnershipTypeList();
                return responseMsg(true,"",remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            {
                return responseMsg(true,"",$data);
            }
        }
        catch(Exception $e)
        {

            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function getCitizenApplication(Request $request)
    {
        try{
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $connection         = WaterApplication::select("water_applications.id",
                                        "water_applications.application_no",
                                        "water_applications.address",
                                        "water_applications.payment_status",
                                        "water_applications.doc_status",
                                        "charges.amount",
                                        DB::raw("'connection' AS type,water_applications.apply_date::date AS apply_date")
                                        )
                                        ->join(
                                            DB::raw("( 
                                                SELECT DISTINCT(water_applications.id) AS application_id , SUM(COALESCE(amount,0)) AS amount
                                                FROM water_applications 
                                                LEFT JOIN water_connection_charges 
                                                    ON water_applications.id = water_connection_charges.application_id 
                                                    AND ( 
                                                        water_connection_charges.paid_status ISNULL  
                                                        OR water_connection_charges.paid_status=FALSE 
                                                    )  
                                                    AND( 
                                                            water_connection_charges.status = TRUE
                                                            OR water_connection_charges.status ISNULL  
                                                        )
                                                WHERE water_applications.user_id = $refUserId
                                                    AND water_applications.ulb_id = $refUlbId
                                                GROUP BY water_applications.id
                                                ) AS charges
                                            "),
                                        function($join){
                                            $join->on("charges.application_id","water_applications.id");
                                        })
                                // ->whereNotIn("status",[0,6,7])
                                ->where("water_applications.user_id",$refUserId)
                                ->where("water_applications.ulb_id",$refUlbId)
                                ->get();
            return responseMsg(true,"",$connection);
        }
        catch(Exception $e)
        {

            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    #---------- core function --------------------------------------------------
    public function getPropertyTypeList()
    {
        // try {
        //     $data = WaterPropertyTypeMstr::select('water_connection_type_mstrs.id', 'water_connection_type_mstrs.connection_type')
        //         ->where('status', 1)
        //         ->get();
        //     return $data;
        // } catch (Exception $e) 
        // {
        //     return [];
        // }
    }
    public function getOwnershipTypeList()
    {
        
    }    
}