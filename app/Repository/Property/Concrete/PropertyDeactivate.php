<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropDeactivationReqInbox;
use App\Models\Property\PropDeactivationRequest;
use App\Models\Property\PropOwnerDtl;
use App\Models\Property\PropProperty;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IPropertyDeactivate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PropertyDeactivate implements IPropertyDeactivate
{
    /**
     * | Created On -19-11-2022
     * | Created By - Sandeep Bara
     * -----------------------------------------------------------------------------------------
     * | Property Deactivation WorkFlow 
     * | status (Open)
    */
    protected $_common;
    public function __construct()
    {
        $this->_common = new CommonFunction();
    }
    public function readHoldigbyNo(Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mUserType  = $this->_common->userType();
            $rules["holdingNo"] = "required|string";
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $mHoldingNo = strtoupper($request->holdingNo);
            $prperty = PropProperty::select("id","new_holding_no","holding_no",
                                        DB::raw("owners.owner_name, owners.guardian_name, owners.mobile_no")
                                    )
                                    ->leftjoin(DB::raw("(SELECT DISTINCT(property_id) AS property_id,
                                                        STRING_AGG(owner_name, ',') AS owner_name,
                                                        STRING_AGG(guardian_name, ',') AS guardian_name,
                                                        STRING_AGG(mobile_no, ',') AS mobile_no
                                                    FROM prop_owner_dtls 
                                                    JOIN prop_properties ON prop_properties.id = prop_owner_dtls.property_id
                                                        AND  prop_properties.status =1 and upper(prop_properties.new_holding_no) = '$mHoldingNo'
                                                        AND prop_properties.ulb_id = $refUlbId
                                                    WHERE prop_owner_dtls.status =1 
                                                    GROUP BY property_id 
                                                    )owners"), function($join){
                                                        $join->on("owners.property_id","prop_properties.id");
                                                    }
                                    )
                                    ->where("prop_properties.new_holding_no",$mHoldingNo)
                                    ->where("prop_properties.ulb_id",$refUlbId)
                                    ->first();
            return responseMsg(true,"",remove_null($prperty));

        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }

    }
    public function deactivatProperty($propId,Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
            $mUserType  = $this->_common->userType();
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                            ->where('ulb_id', $refUlbId)
                            ->first();
            if(!in_array($mUserType,['BO',"SUPER ADMIN"]))
            {
                throw new Exception("You Are Not Authorized For Deactivate Property!");
            }
            $mProperty  = PropProperty::find($propId);
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $mRole = $this->_common->getUserRoll($refUserId,$refUlbId,$workflowId->wf_master_id);  
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
                throw New Exception("Property Not Found");
            }
            $mOwrners = $this->getPropOwnerByProId($mProperty->id);
            if($request->getMethod()=="GET")
            {
                $data["userType"]  = $mUserType;
                $data["property"] = $mProperty;
                $data['owners']   = $mOwrners;
                $data = remove_null($data);
                return responseMsg(true,"",$data);                
            }
            elseif($request->getMethod()=="POST")
            {
                $rules["comments"] = "required|min:10|regex:$mRegex";
                $rules["document"]="required|mimes:pdf,jpg,jpeg,png|max:2048";
                $validator = Validator::make($request->all(), $rules,);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                
                DB::beginTransaction();
                $PropDeactivationRequest    = new PropDeactivationRequest;
                $PropDeactivationRequest->ulb_id        = $refUlbId;
                $PropDeactivationRequest->property_id    = $mProperty->id;
                $PropDeactivationRequest->emp_detail_id  = $refUserId;
                $PropDeactivationRequest->remarks        = $request->comments;
                $PropDeactivationRequest->save();
                $DeactivationReqId = $PropDeactivationRequest->id;
                if($DeactivationReqId)
                {
                    $file = $request->file("document");
                    $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                    $fileName = "Deactive/$DeactivationReqId.$file_ext";
                    $filePath = $this->uplodeFile($file,$fileName);
                    
                    $PropDeactivationRequest->documents = $filePath ;
                    $PropDeactivationRequest->save();
                    $Inbox                  = new PropDeactivationReqInbox();
                    $Inbox->request_id      = $DeactivationReqId;
                    $Inbox->sender_type_id  = $mRole->role_id??0;
                    $Inbox->reciver_type_id = $init_finish["initiator"]['id'];
                    $Inbox->forword_date    =  $mNowDate;
                    $Inbox->forword_time    = Carbon::now()->format('H:s:i');
                    $Inbox->sender_user_id  = $refUlbId;
                    $Inbox->remarks         = $request->comments;
                    $Inbox->save();
                }
                DB::commit();

                return  responseMsg(true,"Property Deactivation Request Apply Succesfully!",[]);

            }

        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }

    }
    


    #---------------------Core Function--------------------------------------------------------
    public function getPropDtlByHoldingNo(string $holdingNo,$ulbId)
    {
        try{
            $mProperty = PropProperty::select("*")
                        ->where("new_holding_no",$holdingNo)
                        ->where("status",1)
                        ->where("ulb_id",$ulbId)
                        ->first();
            return $mProperty;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            return null;
        }
    }
    
    public function getPropOwnerByProId($propId)
    {
        try{
            $mOwrners = PropOwnerDtl::select("*")
                        ->where("property_id",$propId)
                        ->where("status",1)
                        ->get();
            return $mOwrners;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            return null;
        }
    }
    public function uplodeFile($file,$custumFileName)
    {
        $filePath = $file->storeAs('uploads/Property', $custumFileName, 'public');
        return  $filePath;
    }
}