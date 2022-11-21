<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\Property\PropDeactivationReqInbox;
use App\Models\Property\PropDeactivationRequest;
use App\Models\Property\PropOwner;
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
    /**
     * | Searching the valide Property With New Holding No
         query cost(**)
     * |
     * |-----------------------------------------------------------------------
     * | @var refUser    = Auth()->user()       | loging user Data
     * | @var refUserId  = refUser->id          | loging user Id
     * | @var refUlbId   = refUser->ulb_id      | loging user Ulb Id
     * |
     * | @var mHoldingNo = strtoupper(request->holdingNo) | request data
     * | @var property   
     */
    public function readHoldigbyNo(Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
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
            if(sizeOf($property)<1)
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

    /**
     * | Apply The Property Deactivation Request With Proper Comment And Document
        query(***)
     * |
     * |------------------------------------------------------------------------------
     * | @var refUser    = Auth()->user()           | loging user data
     * | @var refUserId  = refUser->id              | loging user Id
     * | @var refUlbId   = refUser->ulb_id          | loging user Ulb Id
     * | @var mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/'  | rejex
     * | @var mNowDate   = Carbon::now()->format("Y-m-d")       | current date
     * | @var refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID')  | workflowId
     * | @var mUserType  = this->_common->userType(refWorkflowId) | loging user short Role Name On Currert Workflow
     * | @var workflowId  (model)WfWorkflow->id
     * |
     * | @var mProperty  = this->getPropertyById(propId)    | property data
     * | @var mRole      = this->_common->getUserRoll(refUserId, refUlbId, workflowId->wf_master_id)    | current user role Dtl
     * | @var init_finish = this->_common->iniatorFinisher(refUserId,refUlbId,refWorkflowId)            | determin the Initiator And Finisher Of The Workflow
     * | @var mOwrners = $this->getPropOwnerByProId(mProperty->id)      | request Property Owners Dtls
     * | 
     * | @var PropDeactivationRequest    = PropDeactivationRequest (model)
     * |
     * |---------------------fuctions---------------------------------------------------------------
     * | this->_common->userType(refWorkflowId)
     * | this->getPropertyById(propId)
     * | this->_common->getUserRoll(refUserId, refUlbId, workflowId->wf_master_id)
     * | this->_common->iniatorFinisher(refUserId, refUlbId, refWorkflowId)
     * | this->getPropOwnerByProId(mProperty->id)
     */
    public function deactivatProperty($propId,Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');            
            $mUserType  = $this->_common->userType($refWorkflowId);
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                            ->where('ulb_id', $refUlbId)
                            ->first();
            if(!in_array($mUserType,['BO',"SUPER ADMIN"]))
            {
                throw new Exception("You Are Not Authorized For Deactivate Property!");
            }
            $mProperty  = $this->getPropertyById($propId);
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
                
                $mProperty  = PropProperty::find($propId);
                if(!$mProperty)
                {
                    throw New Exception("Property Not Found");
                }
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
            $mJoins="";
            $mUserType = $this->_common->userType($refWorkflowId);
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
                $mJoins = "leftjoin";
            }
            else
            {
                $mJoins = "join";
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);
            $mWardIds = implode(',',$mWardIds);
            $mRoleId = $mRole->role_id;   
            $inputs = $request->all(); 
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $mWardIds = $inputs['wardNo']; 
            } 
            
            // DB::enableQueryLog();          
            $mProperty = PropDeactivationRequest::select("prop_deactivation_requests.id",
                                            "properties.holding_no",
                                            "properties.new_holding_no",
                                            "properties.owner_name",
                                            "properties.guardian_name",
                                            "properties.mobile_no",
                                            "properties.email_id",
                                            DB::raw("prop_deactivation_req_inboxes.id AS level_id")
                                            )
                        ->$mJoins("prop_deactivation_req_inboxes",function($join) use($mRoleId){
                                $join->on("prop_deactivation_req_inboxes.request_id","prop_deactivation_requests.id")
                                ->where("prop_deactivation_req_inboxes.receiver_type_id",$mRoleId)
                                ->where("prop_deactivation_req_inboxes.status",1)
                                ->where("prop_deactivation_req_inboxes.verification_status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            prop_properties.id,holding_no,new_holding_no
                                        FROM prop_properties  
                                        LEFT JOIN prop_owners ON prop_properties.id = prop_owners.property_id AND prop_owners.status=1
                                        WHERE prop_properties.status =1 AND prop_properties.ulb_id=$refUlbId
                                        AND prop_properties.ward_mstr_id IN ($mWardIds)
                                        GROUP BY prop_properties.id,holding_no,new_holding_no
                                        )properties"),function($join) use($inputs,$mWardIds){
                                            $join = $join->on("properties.id","prop_deactivation_requests.property_id");
                                            if(isset($inputs['key']) && trim($inputs['key']))
                                            {
                                                $key = trim($inputs['key']);
                                                $join = $join->where(function ($query) use ($key) {
                                                    $query->orwhere('properties.holding_no', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.new_holding_no', 'ILIKE', '%' . $key . '%')                                            
                                                        ->orwhere('properties.owner_name', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.guardian_name', 'ILIKE', '%' . $key . '%')
                                                        ->orwhere('properties.mobile_no', 'ILIKE', '%' . $key . '%');
                                                });
                                            }                                            
                                            // $join = $join->whereIn('properties.ward_mstr_id', $mWardIds);
                                        }
                        )                       
                        ->where("prop_deactivation_requests.ulb_id",$refUlbId);            
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $mProperty = $mProperty
                            ->whereBetween('prop_deactivation_req_inboxes.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }

            if($mRole->is_initiator)
            {
                $mProperty = $mProperty->whereIn('prop_deactivation_requests.status',[1,2]);
            }
            else
            {
                $mProperty = $mProperty->whereIn('prop_deactivation_requests.status',[2]);
            }           
            $mProperty = $mProperty
                    ->get();
            // dd(DB::getQueryLog());
            $data = [
                "wardList"=>$mWardPermission,                
                "property"=>$mProperty,
                "userType"=>$mUserType,
            ] ;           
            return responseMsg(true, "", remove_null($data));
            
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function postNextLevel(Request $request)
    {
        try{
            $receiver_user_type_id="";
            $sms = "";
            $mRequestPending=2;
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\-, \s]+$/';
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');                        
            $mUserType = $this->_common->userType($refWorkflowId); 
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->_common->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);   
            $init_finish = $this->_common->iniatorFinisher($user_id,$ulb_id,$refWorkflowId);         
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $role_id = $role->role_id;           
            $rules = [
                "btn" => "required|in:btc,forward,backward",
                "requestId" => "required|int",
                "comment" => "required|min:10|regex:$regex",
            ];
            $message = [
                "btn.in"=>"Button Value must be In BTC,FORWARD,BACKWARD",
                "comment.required" => "Comment Is Required",
                "comment.min" => "Comment Length can't be less than 10 charecters",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            if($role->is_initiator && in_array($request->btn,['btc','backward']))
            {
               throw new Exception("Initator Can Not send Back The Application");
            }
            $refDeactivationReq = PropDeactivationRequest::find($request->requestId);            
            $mLevelData = $this->getLevelData($request->requestId);
            if(!$refDeactivationReq)
            {
                throw new Exception("Data Not Found");
            }
            elseif($refDeactivationReq->pending_status==5)
            {
                throw new Exception("Deactivation Request Is Already Approved");
            }
            elseif(!$role->is_initiator && isset($mLevelData->receiver_user_type_id) && $mLevelData->receiver_user_type_id != $role->role_id)
            {
                throw new Exception("You are not authorised for this action");
            }
            elseif(!$role->is_initiator && ! $mLevelData)
            {
                throw new Exception("Data Not Found On Level. Please Contact Admin!!!...");
            }  
            elseif(isset($mLevelData->receiver_type_id) && $mLevelData->receiver_type_id != $role->role_id)
            {
                throw new Exception("You Have Already Taken The Action On This Application");
            }           
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["finisher"])
            {
                throw new Exception("Finisher Not Available. Please Contact Admin !!!...");
            }
            
           
            if($request->btn=="forward" && !$role->is_finisher && !$role->is_initiator)
            {
                $sms ="Application Forwarded To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            }
            elseif($request->btn=="backward" && !$role->is_initiator)
            {
                $sms ="Application Forwarded To ".$role->backword_name;
                $receiver_user_type_id = $role->backward_role_id;
                $mRequestPending = $init_finish["initiator"]['id']==$role->backward_role_id ? 3 : $mRequestPending;
            }
            elseif($request->btn=="btc" && !$role->is_initiator)
            {
                $mRequestPending = 0;
                $sms ="Application Forwarded To ".$init_finish["initiator"]['role_name'];
                $receiver_user_type_id = $init_finish["initiator"]['id'];
            } 
            elseif($request->btn=="forward" && !$role->is_initiator && $mLevelData)
            {
                $sms ="Application Forwarded ";
                $receiver_user_type_id = $mLevelData->sender_user_type_id;
            }
            elseif($request->btn=="forward" && $role->is_initiator && !$mLevelData)
            {
                $mRequestPending = 2;
                $sms ="Application Forwarded To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;

            } 
            elseif($request->btn=="forward" && $role->is_initiator && $mLevelData)
            {
                $mRequestPending = 2;
                $sms ="Application Forwarded To ";
                $receiver_user_type_id = $mLevelData->sender_user_type_id;

            } 
            if(!$role->is_finisher && !$receiver_user_type_id)  
            {
                throw new Exception("Next Role Not Found !!!....");
            }
            
            DB::beginTransaction();
            if($mLevelData)
            {
                
                $mLevelData->verification_status = 1;
                $mLevelData->receiver_user_id =$user_id;
                $mLevelData->remarks =$request->comment;
                $mLevelData->forward_date =Carbon::now()->format('Y-m-d');
                $mLevelData->forward_time =Carbon::now()->format('H:s:i');
                $mLevelData->update();
            }
            if(!$role->is_finisher || in_array($request->btn,["backward"]))
            {                
                $level_insert = new PropDeactivationReqInbox;
                $level_insert->request_id = $refDeactivationReq->id;
                $level_insert->sender_type_id = $role_id;
                $level_insert->receiver_type_id = $receiver_user_type_id;
                $level_insert->sender_user_id = $user_id;
                $level_insert->save();
            }
            if(in_array($request->btn,["btc"]))
            {                
                $mRequestPending = 0;
                $refDeactivationReq->update();
            }
            if($role->is_finisher && $request->btn=="forward")
            {
                $sms="Property Deactivated Successfully";
                $mRequestPending = 5;
                $PropProperty  = PropProperty::find($refDeactivationReq->property_id) ;
                $PropProperty->status=0;
                $PropProperty->update();                  
            }
            $refDeactivationReq->status = $mRequestPending;
            $refDeactivationReq->update();  
            DB::commit();
            return responseMsg(true, $sms, "");

        }
        catch(Exception $e)
        { 
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function readDeactivationReq(Request $request)
    {
        try{
            $refWorkflowId  = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');
            $mUserType = $this->_common->userType($refWorkflowId);
            $rules = [
                "requestId" => "required|int",
            ];
            $message = [
                "comment.required" => "Comment Is Required",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $refRequestData =  PropDeactivationRequest::find($request->requestId);
            if(!$refRequestData)
            {
                throw new Exception("Data Not Found!");
            }
            $refProperty = $this->getPropertyById($refRequestData->property_id);
            $refOwners   = $this->getPropOwnerByProId($refRequestData->property_id);
            $refTimeLine = $this->getTimelin($request->requestId);
            $data=[
                "requestData"=> $refRequestData,
                "property"   => $refProperty,
                "owners"     => $refOwners,
                'remarks'    => $refTimeLine,
                "userType"   => $mUserType,
            ];

            return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
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
    public function getPropertyById($id)
    {
        try{
            $application = PropProperty::select("prop_properties.*","ref_prop_ownership_types.ownership_type",
                            "ref_prop_types.property_type",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no")
                    )
                ->leftjoin("ulb_ward_masters",function($join){
                    $join->on("ulb_ward_masters.id","=","prop_properties.ward_mstr_id");                                
                })
                ->leftjoin("ulb_ward_masters AS new_ward",function($join){
                    $join->on("new_ward.id","=","prop_properties.new_ward_mstr_id");                                
                })
                ->leftjoin("ref_prop_ownership_types","ref_prop_ownership_types.id","prop_properties.ownership_type_mstr_id")
                ->leftjoin("ref_prop_types","ref_prop_types.id","prop_properties.prop_type_mstr_id")            
                ->where('prop_properties.id',$id)   
                ->first();
            return $application;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
    }
    public function getPropOwnerByProId($propId)
    {
        try{
            $mOwrners = PropOwner::select("*")
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
    public function getLevelData(int $requestId)
    {
        try{
            $data = PropDeactivationReqInbox::select("*")
                    ->where("request_id",$requestId)
                    ->where("status",1)
                    ->where("verification_status",0)
                    ->orderBy("id","DESC")
                    ->first();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getTimelin($id)
    {
        try{
           
            $time_line =  PropDeactivationReqInbox::select(
                        "prop_deactivation_req_inboxes.remarks",
                        "prop_deactivation_req_inboxes.forward_date",
                        "prop_deactivation_req_inboxes.forward_time",
                        "prop_deactivation_req_inboxes.receiver_type_id",
                        "role_name",
                        DB::raw("prop_deactivation_req_inboxes.created_at as receiving_date")
                    )
                    ->leftjoin(DB::raw("(SELECT receiver_type_id::bigint, request_id::bigint, remarks
                                        FROM prop_deactivation_req_inboxes 
                                        WHERE request_id = $id
                                    )remaks_for"
                                ),function($join){
                                $join->on("remaks_for.receiver_type_id","prop_deactivation_req_inboxes.sender_type_id");
                                // ->where("remaks_for.licence_id","trade_level_pendings.licence_id");
                                }
                    )
                    ->leftjoin('wf_roles', "wf_roles.id", "prop_deactivation_req_inboxes.receiver_type_id")
                    ->where('prop_deactivation_req_inboxes.request_id', $id)     
                    ->whereIn('prop_deactivation_req_inboxes.status',[1,2])                 
                    ->groupBy('prop_deactivation_req_inboxes.receiver_type_id',
                            'prop_deactivation_req_inboxes.remarks',
                            'prop_deactivation_req_inboxes.forward_date',
                            'prop_deactivation_req_inboxes.forward_time','wf_roles.role_name',
                            'prop_deactivation_req_inboxes.created_at'
                    )
                    ->orderBy('prop_deactivation_req_inboxes.created_at', 'desc')
                    ->get();
            return $time_line;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
}