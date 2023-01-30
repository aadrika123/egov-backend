<?php

namespace App\Http\Controllers\Property;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyDeactivation\reqDeactivatProperty;
use App\Http\Requests\Property\PropertyDeactivation\reqPostNext;
use App\Http\Requests\Property\PropertyDeactivation\reqReadProperty;
use App\Models\Property\PropActiveDeactivationRequest;
use App\Models\Property\PropDeactivationRequest;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Concrete\PropertyDeactivate;
use App\Repository\Property\Interfaces\IPropertyDeactivate;
use App\Repository\Property\Interfaces\iSafRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PropertyDeactivateController extends Controller
{
     /**
     * | Created On-19-11-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Deactivation)
     */
    private $Repository;
    private $saf_repository;
    protected $_common;
    protected $_modelWard;
    public function __construct(IPropertyDeactivate $PropertyDeactivate,iSafRepository $saf_repository)
    {
        $this->Repository = $PropertyDeactivate ;
        $this->saf_repository = new ActiveSafController($saf_repository);
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
    }
    public function readHoldigbyNo(Request $request)
    {
        return $this->Repository->readHoldigbyNo($request);
    }
    public function readPorertyById(reqReadProperty $request)
    {
        try{
            $mProperty = $this->saf_repository->getPropByHoldingNo($request);
            if(!$mProperty->original['status'])
            {
                throw new Exception($mProperty->original['message']);
            }
            if($mProperty->original['data']['status']!=1)
            {
                throw new Exception("Property Alerady Deactivated");
            }
            $PropDeactivationRequest    = PropActiveDeactivationRequest::select("*")
                                              ->where("property_id",$request->propertyId)
                                              ->where("status",1)
                                              ->orderBy("id","DESC")
                                              ->first();
            if($PropDeactivationRequest)
            {
                throw new Exception("Request is already submited. Please check request status...!");
            }
            return responseMsgs(true,$mProperty->original['message'],$mProperty->original['data'], "00001", "1.0", "", "POST", $request->deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(), "00001", "1.0", "", "POST", $request->deviceId);
        }
        
    }
    public function deactivatProperty(reqDeactivatProperty $request)
    {
        try{
            $PropDeactivationRequest    = PropDeactivationRequest::select("*")
                                              ->where("property_id",$request->propertyId)
                                              ->where("status",1)
                                              ->orderBy("id","DESC")
                                              ->first();
            if($PropDeactivationRequest)
            {
                throw new Exception("Request is already submited. Please check request status...!");
            }
            return $this->Repository->deactivatProperty($request);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(), "00002", "1.0", "", "POST", $request->deviceId);
             
        }
    }
    public function inbox(Request $request)
    {
        return $this->Repository->inbox($request);
    }
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }
    public function postNextLevel(reqPostNext $request)
    {
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $refDeactivationReq = PropActiveDeactivationRequest::find($request->applicationId);
            $role = $this->_common->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($user_id,$ulb_id,$refWorkflowId); 
            if(!$refDeactivationReq)
            {
                throw new Exception("Data Not Found");
            }
            if($refDeactivationReq->pending_status==5)
            {
                throw new Exception("Deactivation Request Is Already Approved");
            }
            if($refDeactivationReq->current_role!=$role->role_id)
            {
                throw new Exception("You are not authorised for this action");
            }          
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            if(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            if(!$init_finish["finisher"])
            {
                throw new Exception("Finisher Not Available. Please Contact Admin !!!...");
            }
            $allRolse = collect($this->_common->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
            $max_rolse = $refDeactivationReq->max_level_attained;
            $receiverRole = array_values(objToArray($allRolse->where("id",$request->receiverRoleId)))[0]??[];
            
            $sms ="Application BackWord To ".$receiverRole["role_name"]??"";
            if($max_rolse>$receiverRole["serial_no"]??0)
            {
                $sms ="Application Forward To ".$receiverRole["role_name"]??"";
            }
            DB::beginTransaction();
            if($max_rolse<$receiverRole["serial_no"]??0)
            {
                $refDeactivationReq->current_role = $request->receiverRoleId;
                $refDeactivationReq->update();
            }
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $refWorkflowId;
            $metaReqs['refTableDotId'] = 'prop_active_deactivation_requests';
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);

            DB::commit();

            return responseMsgs(true, $sms, "", "00003", "1.0", "", "POST", $request->deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(), "00003", "1.0", "", "POST", $request->deviceId);
        }
        
    }
    public function readDeactivationReq(Request $request)
    {
        return $this->Repository-> readDeactivationReq($request);
    }
    
}
