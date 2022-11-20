<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
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
    protected $_modelWard;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
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
            $property = PropProperty::select("id","new_holding_no","holding_no","prop_address",
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
                                    ->get();
            if(!$property)
            {
                throw new Exception("Holding Not Found");
            }
            $data['property'] = $property;
            return responseMsg(true,"",remove_null($data));

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
    public function inbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');
            $refWorkflowMstrId     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                                    ->where('ulb_id', $refUlbId)
                                    ->first();
            if (!$refWorkflowMstrId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $mUserType = $this->_common->userType();
            $mWardPermission = $this->_common->WardPermission($refUserId);           
            $mRole = $this->_common->getUserRoll($refUserId,$refUlbId,$refWorkflowMstrId->wf_master_id);
            if (!$mRole) 
            {
                throw new Exception("You Are Not Authorized For This Action");
            } 
            if($mRole->is_initiator )    //|| in_array(strtoupper($apply_from),["JSK","SUPER ADMIN","ADMIN","TL","PMU","PM"])
            {
                $mWardPermission = $this->_modelWard->getAllWard($refUlbId)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);

            $mRoleId = $mRole->role_id;   
            $inputs = $request->all();  
            // DB::enableQueryLog();          
            $mProperty = PropDeactivationRequest::select("prop_deactivation_requests.id",
                                            "properties.holding_no",
                                            "properties.new_holding_no",
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id",
                                            DB::raw("prop_deactivation_req_inboxes.id AS level_id")
                                            )
                        ->join("prop_deactivation_req_inboxes",function($join) use($mRoleId){
                                $join->on("prop_deactivation_req_inboxes.request_id","prop_deactivation_requests.id")
                                ->where("prop_deactivation_req_inboxes.reciver_type_id",$mRoleId)
                                ->where("prop_deactivation_req_inboxes.status",1)
                                ->where("prop_deactivation_req_inboxes.verification_status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            prop_properties.id,holding_no,new_holding_no
                                        FROM prop_properties  
                                        LEFT JOIN prop_owner_dtls ON prop_properties.id = prop_owner_dtls.property_id AND prop_owner_dtls.status=1
                                        WHERE prop_properties.status =1 AND prop_properties.ulb_id=$refUlbId
                                        GROUP BY prop_properties.id,holding_no,new_holding_no
                                        )properties"),function($join) use($inputs,$mWardIds){
                                            $local = $join->on("properties.id","prop_deactivation_requests.property_id");
                                            if(isset($inputs['key']) && trim($inputs['key']))
                                            {
                                                $key = trim($inputs['key']);
                                                $local = $local->where(function ($query) use ($key) {
                                                    $query->orwhere('properties.holding_no', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.new_holding_no', 'ILIKE', '%' . $key . '%')                                            
                                                        ->orwhere('properties.owner_name', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.guardian_name', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.mobile_no', 'ILIKE', '%' . $key . '%');
                                                });
                                            }
                                            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
                                            {
                                                $mWardIds = $inputs['wardNo']; 
                                            }
                                            $local = $local->whereIn('prop_properties.ward_mstr_id', $mWardIds);
                                        })
                        ->where("prop_deactivation_requests.status",1)                        
                        ->where("prop_deactivation_requests.ulb_id",$refUlbId);            
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $mProperty = $mProperty
                            ->whereBetween('prop_deactivation_req_inboxes.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }           
            $mProperty = $mProperty
                    ->get();
            // dd(DB::getQueryLog());
            $data = [
                "wardList"=>$mWardPermission,                
                "Property"=>$mProperty,
            ] ;           
            return responseMsg(true, "", $data);
            
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
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