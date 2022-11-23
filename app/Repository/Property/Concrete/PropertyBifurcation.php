<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\Property\PropFloor;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PropertyBifurcation implements IPropertyBifurcation
{

    protected $_common;
    protected $_modelWard;
    protected $_Saf;
    protected $_property;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
        $this->_property = new PropertyDeactivate();
    }
    public function addRecord(Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mProperty  = $this->_property->getPropertyById($request->id);
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');            
            $mUserType  = $this->_common->userType($refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId); 
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }       
            if(!$mProperty)
            {
                throw new Exception("Property Not Found");
            }
            $mOwrners  = $this->_property->getPropOwnerByProId($mProperty->id);
            $mFloors    = $this->getFlooreDtl($mProperty->id);
            if($request->getMethod()=="GET")
            {
                $data = [
                    "property"=>$mProperty,
                    "owners"    => $mOwrners,
                    "floors"   => $mFloors,
                ];
                return responseMsg(true,'',$data);
            }
            elseif($request->getMethod()=="POST")
            {
                echo("POST");
            }
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    #------------------------------CORE Function ---------------------------------------------
    public function getFlooreDtl($propertyId)
    {
        try{
            $mFloors = PropFloor::select("*")
                        ->where("status",1)
                        ->where("property_id",$propertyId)
                        ->get();
            return $mFloors;
        }
        catch(Exception $e)
        {
            return [];
        }
    }
}