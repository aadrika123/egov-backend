<?php

namespace App\Repository\Water\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\UlbMaster;
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
                $data["firmTypeList"]       = $this->getFirmTypeList();
                $data["ownershipTypeList"]  = $this->getOwnershipTypeList();
                $data["categoryTypeList"]   = $this->getCategoryList();
                $data["natureOfBusiness"]   = $this->getItemsList(true);
                if(isset($request->id) && $request->id  && $mApplicationTypeId !=1)
                {
                    $refOldLicece = $this->getLicenceById($request->id); // recieving olde lisense id from url
                    if(!$refOldLicece)
                    {
                        throw new Exception("No Priviuse Licence Found");
                    }
                    $refOldOwneres =$this->getOwnereDtlByLId($request->id);
                    $mnaturOfBusiness = $this->getLicenceItemsById($refOldLicece->nature_of_bussiness);
                    $natur = array();
                    foreach($mnaturOfBusiness as $val)
                    {
                        $natur[]=["id"=>$val->id,
                            "trade_item" =>"(". $val->trade_code.") ". $val->trade_item
                        ];
                    }
                    $refOldLicece->nature_of_bussiness = $natur;

                    $data["licenceDtl"]     =  $refOldLicece;
                    $data["ownerDtl"]       = $refOldOwneres;
                }
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

    #---------- core function --------------------------------------------------
    // public function getConnectionType()
    // {
    //     try {
    //         $connectionTypes = WaterConnectionTypeMstr::table('water_connection_type_mstrs')
    //             ->select('water_connection_type_mstrs.id', 'water_connection_type_mstrs.connection_type')
    //             ->where('status', 1)
    //             ->get();
    //         return $connectionTypes;
    //     } catch (Exception $e) 
    //     {
    //         return [];
    //     }
    // }
}